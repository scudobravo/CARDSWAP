<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\Team;
use App\Models\Player;
use App\Models\CardSet;
use App\Models\GradingCompany;
use App\Models\GradingScore;
use App\Models\CardModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FootballFilterController extends Controller
{
    /**
     * Get all available filter options for football cards
     */
    public function getFilterOptions()
    {
        // Rarities dinamiche dal DB: SOLO rarità base (senza variation)
        $dynamicRarities = CardModel::whereHas('category', function($q) {
                $q->where('slug', 'calcio');
            })
            ->where('is_active', true)
            ->whereNotNull('rarity')
            ->where('rarity', '!=', '')
            ->distinct()
            ->orderBy('rarity')
            ->pluck('rarity')
            ->toArray();

        // Years dinamici dal DB ordinati per anno (prima parte) desc
        $rawYears = CardModel::whereHas('category', function($q) {
                $q->where('slug', 'calcio');
            })
            ->where('is_active', true)
            ->whereNotNull('year')
            ->distinct()
            ->pluck('year')
            ->toArray();

        $sortedYears = collect($rawYears)
            ->map(fn($y) => (string)$y)
            ->sortByDesc(function($y) {
                if (preg_match('/^(\d{4})/', $y, $m)) {
                    return (int)$m[1];
                }
                return (int)preg_replace('/\D/', '', $y) ?: 0;
            })
            ->values()
            ->toArray();

        $filters = [
            'leagues' => League::active()->ordered()->get(['id', 'name', 'slug', 'country']),
            'teams' => Team::active()->ordered()->get(['id', 'name', 'slug', 'city', 'league_id']),
            'players' => Player::active()->ordered()->get(['id', 'name', 'slug', 'position', 'nationality', 'team_id']),
            'card_sets' => CardSet::active()->ordered()->get(['id', 'name', 'slug', 'brand', 'year', 'season']),
            'grading_companies' => GradingCompany::active()->ordered()->get(['id', 'name', 'slug']),
            'grading_scores' => GradingScore::active()->ordered()->get(['id', 'score', 'description', 'short_code', 'is_special', 'grading_company_id']),
            'positions' => ['Attaccante', 'Centrocampista', 'Difensore', 'Portiere'],
            'rarities' => $dynamicRarities,
            'conditions' => ['mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor', 'fair', 'very_good'],
            'years' => $sortedYears,
        ];

        return response()->json($filters);
    }

    /**
     * Get teams filtered by league
     */
    public function getTeamsByLeague(Request $request)
    {
        $request->validate([
            'league_id' => 'required|integer|exists:leagues,id'
        ]);

        $teams = Team::where('league_id', $request->league_id)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'city']);

        return response()->json(['teams' => $teams]);
    }

    /**
     * Get players filtered by team
     */
    public function getPlayersByTeam(Request $request)
    {
        $request->validate([
            'team_id' => 'required|integer|exists:teams,id'
        ]);

        $players = Player::where('team_id', $request->team_id)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'position', 'nationality']);

        return response()->json(['players' => $players]);
    }

    /**
     * Get players filtered by league (all players in teams of that league)
     */
    public function getPlayersByLeague(Request $request)
    {
        $request->validate([
            'league_id' => 'required|integer|exists:leagues,id'
        ]);

        $players = Player::whereHas('team', function($query) use ($request) {
            $query->where('league_id', $request->league_id);
        })
        ->active()
        ->ordered()
        ->get(['id', 'name', 'slug', 'position', 'nationality', 'team_id']);

        return response()->json(['players' => $players]);
    }

    /**
     * Get card sets filtered by year
     */
    public function getCardSetsByYear(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:1990|max:' . (date('Y') + 1)
        ]);

        $cardSets = CardSet::where('year', $request->year)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'brand', 'season']);

        return response()->json(['card_sets' => $cardSets]);
    }

    /**
     * Get card sets filtered by brand
     */
    public function getCardSetsByBrand(Request $request)
    {
        $request->validate([
            'brand' => 'required|string|max:100'
        ]);

        $cardSets = CardSet::where('brand', $request->brand)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'year', 'season']);

        return response()->json(['card_sets' => $cardSets]);
    }

    /**
     * Get grading scores filtered by company
     */
    public function getGradingScoresByCompany(Request $request)
    {
        $request->validate([
            'grading_company_id' => 'nullable|integer|exists:grading_companies,id'
        ]);

        $query = GradingScore::active()->ordered();
        
        if ($request->filled('grading_company_id')) {
            $query->where('grading_company_id', $request->grading_company_id);
        }

        $scores = $query->get(['id', 'score', 'description', 'short_code', 'is_special']);

        return response()->json(['grading_scores' => $scores]);
    }

    /**
     * Search players with autocomplete (minimum 2 characters)
     */
    public function searchPlayers(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'team_id' => 'nullable|integer',
            'set_id' => 'nullable|integer',
            'year' => 'nullable|string',
            'brand' => 'nullable|string'
        ]);

        $query = $request->get('q');
        
        // Filtra solo i player che hanno carte Football (slug = calcio)
        // NON carichiamo la relazione 'team' per rendere il giocatore indipendente
        $playersQuery = Player::whereHas('cardModels', function($q) use ($request) {
                $q->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'calcio');
                });
                
                // Applica filtri aggiuntivi per limitare i risultati
                if ($request->filled('team_id')) {
                    $q->where('team_id', $request->team_id);
                }
                if ($request->filled('set_id')) {
                    $q->where('card_set_id', $request->set_id);
                }
                if ($request->filled('year')) {
                    $q->where('year', $request->year);
                }
                if ($request->filled('brand')) {
                    $q->whereHas('cardSet', function($setQuery) use ($request) {
                        $setQuery->where('brand', $request->brand);
                    });
                }
            })
            ->active()
            ->orderBy('name');
        
        // Se c'è una query di ricerca, filtra i risultati
        if (!empty($query)) {
            $playersQuery->where('name', 'LIKE', "%{$query}%");
        }
        
        $players = $playersQuery->with(['team', 'cardModels' => function($query) {
                $query->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'calcio');
                })->select('id', 'player_id', 'card_number', 'card_number_in_set', 'name', 'year', 'rarity', 'rarity_variation', 'is_rookie', 'is_autograph', 'is_relic', 'is_on_card_auto', 'is_jewel', 'is_booklet', 'is_multi_player_dual', 'is_multi_player_triple', 'is_multi_player_quad', 'card_set_id', 'team_id')
                ->with(['cardSet:id,name,brand', 'team:id,name']);
            }])
            ->limit(100) // Limite ragionevole per evitare errori di memoria
            ->get(['id', 'name', 'slug', 'position', 'nationality', 'team_id']);

        // Raggruppa i giocatori per nome per evitare duplicati
        $groupedPlayers = $players->groupBy('name');
        
        // Trasforma i dati per includere informazioni delle carte e squadra
        $transformedPlayers = $groupedPlayers->map(function($playerGroup, $playerName) {
            // Prendi il primo giocatore del gruppo come rappresentante
            $representativePlayer = $playerGroup->first();
            
            // Raccogli tutte le carte di tutti i giocatori con lo stesso nome
            $allCards = collect();
            $allCardNumbers = collect();
            $allCardNumbersInSet = collect();
            $allTeams = collect();
            
            foreach ($playerGroup as $player) {
                $allCards = $allCards->merge($player->cardModels);
                $allCardNumbers = $allCardNumbers->merge($player->cardModels->pluck('card_number')->filter());
                $allCardNumbersInSet = $allCardNumbersInSet->merge($player->cardModels->pluck('card_number_in_set')->filter());
                if ($player->team) {
                    $allTeams->push($player->team);
                }
            }
            
            // Per giocatori famosi come Messi, cerca tutte le squadre dalle carte
            // indipendentemente dal filtro team corrente
            // Prima cerca TUTTI i giocatori con lo stesso nome nel database
            $allPlayerIdsWithSameName = Player::where('name', $playerName)
                ->pluck('id')
                ->toArray();
            
            // Query completamente separata che ignora il filtro team corrente
            // per trovare tutte le squadre di questo giocatore
            $allTeamsFromCards = \App\Models\CardModel::whereIn('player_id', $allPlayerIdsWithSameName)
                ->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'calcio');
                })
                ->with('team:id,name,slug')
                ->get()
                ->pluck('team')
                ->filter()
                ->unique('id');
            
            // Se abbiamo trovato squadre dalle carte, sostituisci completamente
            // le squadre del team corrente con quelle trovate dalle carte
            if ($allTeamsFromCards->count() > 0) {
                $allTeams = $allTeamsFromCards;
                Log::info('✅ Trovate ' . $allTeamsFromCards->count() . ' squadre per ' . $playerName);
            }
            
            // Rimuovi duplicati
            $uniqueCardNumbers = $allCardNumbers->unique()->values();
            $uniqueCardNumbersInSet = $allCardNumbersInSet->unique()->values();
            $uniqueTeams = $allTeams->unique('id')->values();
            
            // Usa sempre card_number_in_set (colonna C) invece di card_number (colonna A)
            $effectiveCardNumbers = $uniqueCardNumbersInSet;
            
            return [
                'id' => $representativePlayer->id, // Usa l'ID del primo giocatore come rappresentante
                'name' => $playerName,
                'slug' => $representativePlayer->slug,
                'position' => $representativePlayer->position,
                'nationality' => $representativePlayer->nationality,
                'team' => $uniqueTeams->first() ? [
                    'id' => $uniqueTeams->first()->id,
                    'name' => $uniqueTeams->first()->name,
                    'slug' => $uniqueTeams->first()->slug
                ] : null,
                'display_name' => $playerName,
                'card_numbers' => $effectiveCardNumbers,
                'card_numbers_in_set' => $uniqueCardNumbersInSet,
                'has_cards' => $allCards->count() > 0,
                'cards' => $allCards->map(function($card) {
                    return [
                        'id' => $card->id,
                        'name' => $card->name,
                        'year' => $card->year,
                        'rarity' => $card->rarity,
                        'rarity_variation' => $card->rarity_variation,
                        'card_number' => $card->card_number,
                        'card_number_in_set' => $card->card_number_in_set,
                        'is_rookie' => $card->is_rookie ?? false,
                        'is_autograph' => $card->is_autograph ?? false,
                        'is_relic' => $card->is_relic ?? false,
                        'is_on_card_auto' => $card->is_on_card_auto ?? false,
                        'is_jewel' => $card->is_jewel ?? false,
                        'is_booklet' => $card->is_booklet ?? false,
                        'is_multi_player_dual' => $card->is_multi_player_dual ?? false,
                        'is_multi_player_triple' => $card->is_multi_player_triple ?? false,
                        'is_multi_player_quad' => $card->is_multi_player_quad ?? false,
                        'card_set' => $card->cardSet ? [
                            'id' => $card->cardSet->id,
                            'name' => $card->cardSet->name,
                            'brand' => $card->cardSet->brand
                        ] : null,
                        'team' => $card->team ? [
                            'id' => $card->team->id,
                            'name' => $card->team->name
                        ] : null
                    ];
                })->toArray(),
                'all_teams' => $uniqueTeams->map(function($team) {
                    return [
                        'id' => $team->id,
                        'name' => $team->name,
                        'slug' => $team->slug
                    ];
                })->values()->toArray() // Converti in array per compatibilità con il frontend
            ];
        })->values(); // Converte la Collection in array

        return response()->json(['players' => $transformedPlayers]);
    }

    /**
     * Search teams with autocomplete (minimum 2 characters)
     */
    public function searchTeams(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'player_id' => 'nullable|integer',
            'set_id' => 'nullable|integer',
            'year' => 'nullable|string',
            'brand' => 'nullable|string'
        ]);

        $query = $request->get('q');
        
        // Filtra solo i team che hanno carte Football (slug = calcio)
        $teamsQuery = Team::with('league')
            ->whereHas('cardModels', function($q) use ($request) {
                $q->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'calcio');
                });
                
                // Applica filtri aggiuntivi per limitare i risultati
                if ($request->filled('player_id')) {
                    // Se è un singolo player_id, cerca tutte le squadre delle carte di tutti i giocatori con lo stesso nome
                    if (is_array($request->player_id)) {
                        $playerIds = $request->player_id;
                    } else {
                        $player = Player::find($request->player_id);
                        if ($player) {
                            $playerIds = Player::where('name', $player->name)->pluck('id')->toArray();
                        } else {
                            $playerIds = [$request->player_id];
                        }
                    }
                    $q->whereIn('player_id', $playerIds);
                }
                if ($request->filled('set_id')) {
                    $q->where('card_set_id', $request->set_id);
                }
                if ($request->filled('year')) {
                    $q->where('year', $request->year);
                }
                if ($request->filled('brand')) {
                    $q->whereHas('cardSet', function($setQuery) use ($request) {
                        $setQuery->where('brand', $request->brand);
                    });
                }
            })
            ->active()
            ->orderBy('name');
        
        // Se c'è una query di ricerca, filtra i risultati
        if (!empty($query)) {
            $teamsQuery->where('name', 'LIKE', "%{$query}%");
        }
        
        $teams = $teamsQuery->get(['id', 'name', 'slug', 'city', 'league_id']);

        return response()->json(['teams' => $teams]);
    }

    /**
     * Search card sets with autocomplete (minimum 2 characters)
     */
    public function searchCardSets(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'player_id' => 'nullable|integer',
            'team_id' => 'nullable|integer',
            'year' => 'nullable|string',
            'brand' => 'nullable|string'
        ]);

        $query = $request->get('q');
        
        // Filtra solo i set che hanno carte Football (slug = calcio)
        $cardSetsQuery = CardSet::whereHas('cardModels', function($q) use ($request) {
                $q->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'calcio');
                });
                
                // Applica filtri aggiuntivi per limitare i risultati
                if ($request->filled('player_id')) {
                    // In produzione esistono più record Player con lo stesso nome (squadre diverse).
                    // Per coerenza con ricerca Team, includiamo TUTTI gli ID dei giocatori con lo stesso nome.
                    $player = Player::find($request->player_id);
                    if ($player) {
                        $playerIds = Player::where('name', $player->name)->pluck('id')->toArray();
                        $q->whereIn('player_id', $playerIds);
                    } else {
                        $q->where('player_id', $request->player_id);
                    }
                }
                if ($request->filled('team_id')) {
                    $q->where('team_id', $request->team_id);
                }
                if ($request->filled('year')) {
                    $q->where('year', $request->year);
                }
                if ($request->filled('brand')) {
                    $q->whereHas('cardSet', function($setQuery) use ($request) {
                        $setQuery->where('brand', $request->brand);
                    });
                }
            })
            ->active()
            ->orderBy('name');
        
        // Se c'è una query di ricerca, filtra i risultati
        if (!empty($query)) {
            $cardSetsQuery->where('name', 'LIKE', "%{$query}%");
        }
        
        $cardSets = $cardSetsQuery->get(['id', 'name', 'slug', 'brand', 'year', 'season']);

        return response()->json(['card_sets' => $cardSets]);
    }

    /**
     * Get a single player by ID
     * NON carichiamo la relazione 'team' per mantenere il giocatore indipendente dalla squadra
     */
    public function getPlayerById($id)
    {
        $player = Player::with(['team', 'cardModels.cardSet:id,name,brand', 'cardModels.team:id,name'])->find($id);
        
        if (!$player) {
            return response()->json([
                'error' => 'Player not found'
            ], 404);
        }

        // Trasforma i dati per includere informazioni delle carte e squadra
        $cardNumbers = $player->cardModels->pluck('card_number')->filter()->unique()->values();
        $cardNumbersInSet = $player->cardModels->pluck('card_number_in_set')->filter()->unique()->values();
        
        // Usa card_number_in_set se card_number è vuoto
        $effectiveCardNumbers = $cardNumbers->count() > 0 ? $cardNumbers : $cardNumbersInSet;
        
        $transformedPlayer = [
            'id' => $player->id,
            'name' => $player->name,
            'slug' => $player->slug,
            'position' => $player->position,
            'nationality' => $player->nationality,
            'team' => $player->team ? [
                'id' => $player->team->id,
                'name' => $player->team->name,
                'slug' => $player->team->slug
            ] : null,
            'display_name' => $player->name,
            'card_numbers' => $effectiveCardNumbers,
            'card_numbers_in_set' => $cardNumbersInSet,
            'has_cards' => $player->cardModels->count() > 0,
            'cards' => $player->cardModels->map(function($card) {
                return [
                    'id' => $card->id,
                    'name' => $card->name,
                    'year' => $card->year,
                    'rarity' => $card->rarity,
                    'rarity_variation' => $card->rarity_variation,
                    'card_number' => $card->card_number,
                    'card_number_in_set' => $card->card_number_in_set,
                    'is_rookie' => $card->is_rookie ?? false,
                    'is_autograph' => $card->is_autograph ?? false,
                    'is_relic' => $card->is_relic ?? false,
                    'is_on_card_auto' => $card->is_on_card_auto ?? false,
                    'is_jewel' => $card->is_jewel ?? false,
                    'is_booklet' => $card->is_booklet ?? false,
                    'is_multi_player_dual' => $card->is_multi_player_dual ?? false,
                    'is_multi_player_triple' => $card->is_multi_player_triple ?? false,
                    'is_multi_player_quad' => $card->is_multi_player_quad ?? false,
                    'card_set' => $card->cardSet ? [
                        'id' => $card->cardSet->id,
                        'name' => $card->cardSet->name,
                        'brand' => $card->cardSet->brand
                    ] : null,
                    'team' => $card->team ? [
                        'id' => $card->team->id,
                        'name' => $card->team->name
                    ] : null
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'player' => $transformedPlayer
            ]
        ]);
    }

    /**
     * Get a single team by ID
     */
    public function getTeamById($id)
    {
        $team = Team::find($id);
        
        if (!$team) {
            return response()->json([
                'error' => 'Team not found'
            ], 404);
        }

        return response()->json([
            'team' => $team
        ]);
    }

    /**
     * Get a single card set by ID
     */
    public function getCardSetById($id)
    {
        $cardSet = CardSet::find($id);
        
        if (!$cardSet) {
            return response()->json([
                'error' => 'Card set not found'
            ], 404);
        }

        return response()->json([
            'card_set' => $cardSet
        ]);
    }

    /**
     * Get available years from database
     */
    public function getAvailableYears()
    {
        $years = CardSet::select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        return response()->json(['years' => $years]);
    }

    /**
     * Get available brands from database
     */
    public function getAvailableBrands()
    {
        $brands = CardSet::select('brand')
            ->whereNotNull('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->toArray();

        return response()->json(['brands' => $brands]);
    }

    /**
     * Get available rarities from database
     */
    public function getAvailableRarities()
    {
        $rarities = DB::table('card_models')
            ->select('rarity')
            ->whereNotNull('rarity')
            ->distinct()
            ->orderBy('rarity')
            ->pluck('rarity')
            ->toArray();

        return response()->json(['rarities' => $rarities]);
    }

    /**
     * Search rarities with autocomplete (minimum 2 characters)
     */
    public function searchRarities(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'player_id' => 'nullable|integer',
            'team_id' => 'nullable|integer',
            'set_id' => 'nullable|integer',
            'year' => 'nullable|string',
            'brand' => 'nullable|string'
        ]);

        $query = $request->get('q', '');
        
        // Query base per le rarità: SOLO dalla colonna rarity, NON includere rarity_variation
        $raritiesQuery = CardModel::whereHas('category', function($catQuery) {
                $catQuery->where('slug', 'calcio');
            })
            ->where('is_active', true)
            ->whereNotNull('rarity')
            ->where('rarity', '!=', '');
        
        // Applica filtri aggiuntivi per limitare i risultati
        if ($request->filled('player_id')) {
            $player = Player::find($request->player_id);
            if ($player) {
                $playerIds = Player::where('name', $player->name)->pluck('id')->toArray();
                $raritiesQuery->whereIn('player_id', $playerIds);
            } else {
                $raritiesQuery->where('player_id', $request->player_id);
            }
        }
        
        if ($request->filled('team_id')) {
            $raritiesQuery->where('team_id', $request->team_id);
        }
        
        if ($request->filled('set_id')) {
            $raritiesQuery->where('card_set_id', $request->set_id);
        }
        
        if ($request->filled('year')) {
            $raritiesQuery->where('year', $request->year);
        }
        
        if ($request->filled('brand')) {
            $raritiesQuery->whereHas('cardSet', function($setQuery) use ($request) {
                $setQuery->where('brand', $request->brand);
            });
        }
        
        // Se c'è una query di ricerca, filtra i risultati SOLO sulla colonna rarity
        // NON includere rarity_variation nella ricerca
        if (!empty($query) && strlen($query) >= 2) {
            $raritiesQuery->where('rarity', 'LIKE', "%{$query}%");
        }
        
        // Estrai le rarità uniche: SOLO dalla colonna rarity, NON da rarity_variation
        $rarities = $raritiesQuery
            ->select('rarity')
            ->whereNotNull('rarity')
            ->where('rarity', '!=', '')
            ->distinct()
            ->orderBy('rarity')
            ->limit(100) // Limite ragionevole
            ->pluck('rarity')
            ->toArray();
        
        // Applica il filtro di ricerca anche sui risultati finali per sicurezza
        if (!empty($query) && strlen($query) >= 2) {
            $rarities = array_filter($rarities, function($rarity) use ($query) {
                return stripos($rarity, $query) !== false;
            });
            // Riordina dopo il filtro
            sort($rarities);
        }
        
        return response()->json(['rarities' => array_values($rarities)]);
    }

    /**
     * Get chained filter options based on current selections
     */
    public function getChainedFilters(Request $request)
    {
        try {
            $filters = $request->all();
            $response = [];

            // Base query per card_models
            // IMPORTANTE: Non filtrare per cardListings attive quando si crea una nuova inserzione
            // (ad esempio per sealed box/pack) perché non ci sono ancora inserzioni attive
            $cardModelsQuery = \App\Models\CardModel::query()
                ->where('is_active', true)
                ->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'calcio');
                });
            
            // Filtra per cardListings attive SOLO se non stiamo creando una nuova inserzione
            // (cioè se non c'è solo set_id o brand senza player/team, che indica creazione sealed box/pack)
            $isCreatingNewListing = (isset($filters['set_id']) && !empty($filters['set_id'])) 
                && (!isset($filters['player_id']) || empty($filters['player_id']))
                && (!isset($filters['team_id']) || empty($filters['team_id']));
            
            if (!$isCreatingNewListing) {
                $cardModelsQuery->whereHas('cardListings', function($listingQuery) {
                    $listingQuery->where('status', 'active');
                });
            }

        // Filtri a catena: Player → Team → Set → Year → Brand → Rarity
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            $cardModelsQuery->where('player_id', $filters['player_id']);
        }

        if (isset($filters['team_id']) && !empty($filters['team_id'])) {
            $cardModelsQuery->where('team_id', $filters['team_id']);
        }

        if (isset($filters['set_id']) && !empty($filters['set_id'])) {
            $cardModelsQuery->where('card_set_id', $filters['set_id']);
        }

        if (isset($filters['year']) && !empty($filters['year'])) {
            $cardModelsQuery->where('year', $filters['year']);
        }

        if (isset($filters['brand']) && !empty($filters['brand'])) {
            $cardModelsQuery->whereHas('cardSet', function($query) use ($filters) {
                $query->where('brand', $filters['brand']);
            });
        }

        // 1. TEAMS - Dipende da Player selezionato O Brand selezionato
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            // Usa solo il player_id specifico selezionato per la logica "a imbuto"
            $response['teams'] = Team::whereHas('cardModels', function($query) use ($filters) {
                $query->where('player_id', $filters['player_id'])
                      ->whereHas('category', function($catQuery) {
                          $catQuery->where('slug', 'calcio');
                      });
            })
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'city']);
        } elseif (isset($filters['brand']) && !empty($filters['brand'])) {
            // Se è selezionato solo il brand, mostra tutte le squadre per quel brand
            $response['teams'] = Team::whereHas('cardModels', function($query) use ($filters) {
                $query->whereHas('category', function($catQuery) {
                        $catQuery->where('slug', 'calcio');
                    })
                    ->whereHas('cardSet', function($setQuery) use ($filters) {
                        $setQuery->where('brand', $filters['brand']);
                    });
            })
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'city']);
        }

        // 2. SETS - Dipende da Player, Team e/o Brand
        $setsQuery = CardSet::query();
        if (isset($filters['player_id']) || isset($filters['team_id']) || isset($filters['brand'])) {
            $setsQuery->whereHas('cardModels', function($query) use ($filters) {
                $query->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'calcio');
                });
                
                if (isset($filters['player_id']) && !empty($filters['player_id'])) {
                    // Usa solo il player_id specifico selezionato per la logica "a imbuto"
                    $query->where('player_id', $filters['player_id']);
                }
                if (isset($filters['team_id']) && !empty($filters['team_id'])) {
                    $query->where('team_id', $filters['team_id']);
                }
            });
            
            // Filtra per brand se specificato
            if (isset($filters['brand']) && !empty($filters['brand'])) {
                $setsQuery->where('brand', $filters['brand']);
            }
        }
        $response['card_sets'] = $setsQuery->active()->ordered()->get(['id', 'name', 'slug', 'brand', 'year']);

        // 3. PLAYERS - Dipende da Team, Set e/o Brand (se non è già selezionato un player)
        if (!isset($filters['player_id']) || empty($filters['player_id'])) {
            $playersQuery = Player::query();
            if (isset($filters['team_id']) || isset($filters['set_id']) || isset($filters['brand'])) {
                $playersQuery->whereHas('cardModels', function($query) use ($filters) {
                    $query->whereHas('category', function($catQuery) {
                        $catQuery->where('slug', 'calcio');
                    });
                    
                    if (isset($filters['team_id']) && !empty($filters['team_id'])) {
                        $query->where('team_id', $filters['team_id']);
                    }
                    if (isset($filters['set_id']) && !empty($filters['set_id'])) {
                        $query->where('card_set_id', $filters['set_id']);
                    }
                    if (isset($filters['brand']) && !empty($filters['brand'])) {
                        $query->whereHas('cardSet', function($setQuery) use ($filters) {
                            $setQuery->where('brand', $filters['brand']);
                        });
                    }
                });
            }
            $response['players'] = $playersQuery->active()->ordered()->get(['id', 'name', 'slug', 'position', 'nationality', 'team_id']);
        }

        // 4. YEARS - Dipende da Player, Team, Set, Brand
        $yearsQuery = $cardModelsQuery->clone();
        $response['years'] = $yearsQuery->select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // 5. BRANDS - Dipende da Player, Team, Set, Year
        // IMPORTANTE: Crea una nuova query base senza filtri che potrebbero essere ambigui dopo il join
        // Applica i filtri dopo il join con prefisso esplicito della tabella per evitare ambiguità
        $brandsQuery = \App\Models\CardModel::query()
            ->whereHas('category', function($catQuery) {
                $catQuery->where('slug', 'calcio');
            });
        
        // Applica tutti i filtri dalla query originale (tranne quelli che potrebbero essere ambigui dopo il join)
        if (!$isCreatingNewListing) {
            $brandsQuery->whereHas('cardListings', function($listingQuery) {
                $listingQuery->where('status', 'active');
            });
        }
        
        // Filtro brand va applicato prima del join tramite whereHas
        if (isset($filters['brand']) && !empty($filters['brand'])) {
            $brandsQuery->whereHas('cardSet', function($query) use ($filters) {
                $query->where('brand', $filters['brand']);
            });
        }
        
        // Ora fai il join e aggiungi tutti i filtri ambigui esplicitamente con prefisso tabella
        $brandsQuery = $brandsQuery->join('card_sets', 'card_models.card_set_id', '=', 'card_sets.id');
        
        // Applica i filtri a catena DOPO il join con prefisso esplicito della tabella
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            $brandsQuery->where('card_models.player_id', $filters['player_id']);
        }
        if (isset($filters['team_id']) && !empty($filters['team_id'])) {
            $brandsQuery->where('card_models.team_id', $filters['team_id']);
        }
        if (isset($filters['set_id']) && !empty($filters['set_id'])) {
            $brandsQuery->where('card_models.card_set_id', $filters['set_id']);
        }
        if (isset($filters['year']) && !empty($filters['year'])) {
            $brandsQuery->where('card_models.year', $filters['year']);
        }
        
        $response['brands'] = $brandsQuery->select('card_sets.brand')
            ->where('card_models.is_active', true) // Specifica esplicitamente la tabella per evitare ambiguità
            ->distinct()
            ->pluck('card_sets.brand')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        // 6. RARITIES - Dipende da tutti i filtri precedenti
        // SOLO dalla colonna rarity, NON da rarity_variation
        $raritiesQuery = $cardModelsQuery->clone();
        $response['rarities'] = $raritiesQuery
            ->select('rarity')
            ->whereNotNull('rarity')
            ->where('rarity', '!=', '')
            ->distinct()
            ->orderBy('rarity')
            ->pluck('rarity')
            ->toArray();

        // 7. NUMBERED RANGE - Dipende da tutti i filtri precedenti
        $numberedQuery = $cardModelsQuery->clone();
        $numberedValues = $numberedQuery->select('card_number_in_set')
            ->whereNotNull('card_number_in_set')
            ->where('card_number_in_set', '!=', '')
            ->pluck('card_number_in_set')
            ->map(function($value) {
                // Estrai numeri da stringhe come "/50", "25/100", etc.
                if (preg_match('/(\d+)\/(\d+)/', $value, $matches)) {
                    return (int)$matches[1]; // Numero della carta
                }
                return null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($numberedValues->count() > 0) {
            $response['numbered_range'] = [
                'min' => $numberedValues->min(),
                'max' => $numberedValues->max(),
                'available_values' => $numberedValues->toArray()
            ];
        }

        // 7. GRADING vs CONDITION - Mutuamente esclusivi
        $gradingQuery = $cardModelsQuery->clone();
        $hasGrading = $gradingQuery->whereNotNull('grading_company_id')->exists();
        $hasNoGrading = $gradingQuery->clone()->whereNull('grading_company_id')->exists();

        $response['grading_available'] = $hasGrading;
        $response['condition_available'] = $hasNoGrading;

        // 8. GRADING COMPANIES - Solo se ci sono carte con grading
        if ($hasGrading) {
            $response['grading_companies'] = GradingCompany::whereHas('cardModels', function($query) use ($cardModelsQuery) {
                $query->whereIn('id', $cardModelsQuery->clone()->whereNotNull('grading_company_id')->pluck('id'));
            })
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug']);
        }

        // 9. CONDITIONS - Solo se ci sono carte senza grading
        if ($hasNoGrading) {
            // Le condizioni sono hardcoded per ora, ma potrebbero essere dinamiche
            $response['conditions'] = [
                ['value' => 'mint', 'label' => 'Mint'],
                ['value' => 'near_mint', 'label' => 'Near Mint'],
                ['value' => 'excellent', 'label' => 'Excellent'],
                ['value' => 'good', 'label' => 'Good'],
                ['value' => 'light_played', 'label' => 'Light Played'],
                ['value' => 'played', 'label' => 'Played'],
                ['value' => 'poor', 'label' => 'Poor']
            ];
        }

        return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Errore in getChainedFilters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters ?? []
            ]);
            
            return response()->json([
                'years' => [],
                'brands' => [],
                'rarities' => [],
                'teams' => [],
                'card_sets' => [],
                'players' => [],
                'grading_companies' => [],
                'conditions' => [],
                'numbered_range' => null,
                'grading_available' => false,
                'condition_available' => false
            ]);
        }
    }

    /**
     * Get advanced filter options with dependencies (legacy)
     */
    public function getAdvancedFilters(Request $request)
    {
        try {
            // Redirect to chained filters for consistency
            return $this->getChainedFilters($request);
        } catch (\Exception $e) {
            Log::error('Errore in getAdvancedFilters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'years' => [],
                'brands' => [],
                'rarities' => [],
                'teams' => [],
                'card_sets' => [],
                'players' => [],
                'grading_companies' => [],
                'conditions' => []
            ]);
        }
    }

    /**
     * Get filtered products based on filter criteria
     */
    public function getFilteredProducts(Request $request)
    {
        try {
            $filters = $request->all();
        
        // Determina se la sottocategoria è sealed (packs o boxes)
        $isSealed = isset($filters['subcategory']) && 
                    in_array($filters['subcategory'], ['sealed-packs', 'sealed-boxes']);
        
        // IMPORTANTE: Partiamo da CardListing attive (non CardModel)
        // Così mostriamo solo le carte effettivamente in vendita dai venditori
        $query = \App\Models\CardListing::with([
                'cardModel.category',
                'cardModel.player',
                'cardModel.team',
                'gradingCompany',
                'cardModel.cardSet',
                'cardModel.gradingCompany',
                'seller'
            ])
            ->where('status', 'active')
            ->where(function($q) {
                // Per inserzioni con cardModel (singles, bulk)
                $q->whereHas('cardModel', function($cardModelQ) {
                    $cardModelQ->where('is_active', true)
                      ->whereHas('category', function($catQ) {
                          $catQ->where('slug', 'calcio');
                      });
                })
                // Per sealed-pack, sealed-box e lotti (che non hanno cardModel), includili sempre
                // La categoria verrà filtrata più avanti se necessario
                ->orWhere(function($sealedQ) {
                    $sealedQ->where(function($subQ) {
                        $subQ->where('listing_type', 'sealed-pack')
                             ->orWhere('listing_type', 'sealed-box')
                             ->orWhere('listing_type', 'lot');
                    })
                    ->whereNull('card_model_id');
                });
            });

        // Per sealed packs/boxes, applica solo Set, Year e Brand
        // Per altri, applica tutti i filtri disponibili
        if (!$isSealed) {
            // Applica filtri a catena: Player → Team → Set → Year → Brand → Rarity
            if (isset($filters['player_id']) && !empty($filters['player_id'])) {
                // Gestisci sia array che singolo valore (Laravel converte player_id[] in array)
                $playerIds = [];
                if (is_array($filters['player_id'])) {
                    $playerIds = $filters['player_id'];
                } else {
                    $playerIds = [$filters['player_id']];
                }
                
                // Per ogni player_id, cerca tutti i giocatori con lo stesso nome
                $allPlayerIds = [];
                foreach ($playerIds as $playerId) {
                    $player = \App\Models\Player::find($playerId);
                    if ($player) {
                        $sameNamePlayers = \App\Models\Player::where('name', $player->name)->pluck('id')->toArray();
                        $allPlayerIds = array_merge($allPlayerIds, $sameNamePlayers);
                    } else {
                        $allPlayerIds[] = $playerId;
                    }
                }
                
                if (!empty($allPlayerIds)) {
                    $query->whereHas('cardModel', function($q) use ($allPlayerIds) {
                        $q->whereIn('player_id', array_unique($allPlayerIds));
                    });
                }
            }

            if (isset($filters['team_id']) && !empty($filters['team_id'])) {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    $q->where('team_id', $filters['team_id']);
                });
            }

            if (isset($filters['rarity']) && !empty($filters['rarity'])) {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    $q->where('rarity', $filters['rarity']);
                });
            }

            // Filtro per range numerato
            // IMPORTANTE: Il frontend mostra card_number se presente, altrimenti card_number_in_set
            // Quindi dobbiamo filtrare su entrambi i campi, preferendo card_number
            // Gestisce formati come: "1", "5/100", "01-gen", "1-AU", "A-01" (estrae solo il numero iniziale)
            if (isset($filters['numbered_min']) && !empty($filters['numbered_min']) || isset($filters['numbered_max']) && !empty($filters['numbered_max'])) {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    // Filtra su card_number se presente, altrimenti su card_number_in_set
                    // Usa COALESCE per preferire card_number
                    $q->where(function($subQ) use ($filters) {
                        // Caso 1: card_number non è NULL e non è vuoto
                        $subQ->where(function($q1) use ($filters) {
                            $q1->whereNotNull('card_number')
                               ->where('card_number', '!=', '')
                               ->whereRaw(
                                   "REGEXP_SUBSTR(
                                       CASE 
                                           WHEN LOCATE('/', card_number) > 0 
                                           THEN SUBSTRING_INDEX(card_number, '/', 1)
                                           ELSE card_number
                                       END,
                                       '^[0-9]+'
                                   ) IS NOT NULL"
                               );
                            
                            if (isset($filters['numbered_min']) && !empty($filters['numbered_min'])) {
                                $numberedMin = (int)$filters['numbered_min'];
                                $q1->whereRaw(
                                    "CAST(
                                        REGEXP_SUBSTR(
                                            CASE 
                                                WHEN LOCATE('/', card_number) > 0 
                                                THEN SUBSTRING_INDEX(card_number, '/', 1)
                                                ELSE card_number
                                            END,
                                            '^[0-9]+'
                                        ) AS UNSIGNED
                                    ) >= ?",
                                    [$numberedMin]
                                );
                            }
                            
                            if (isset($filters['numbered_max']) && !empty($filters['numbered_max'])) {
                                $numberedMax = (int)$filters['numbered_max'];
                                $q1->whereRaw(
                                    "CAST(
                                        REGEXP_SUBSTR(
                                            CASE 
                                                WHEN LOCATE('/', card_number) > 0 
                                                THEN SUBSTRING_INDEX(card_number, '/', 1)
                                                ELSE card_number
                                            END,
                                            '^[0-9]+'
                                        ) AS UNSIGNED
                                    ) <= ?",
                                    [$numberedMax]
                                );
                            }
                        })
                        // Caso 2: card_number è NULL o vuoto, usa card_number_in_set
                        ->orWhere(function($q2) use ($filters) {
                            $q2->where(function($q3) {
                                $q3->whereNull('card_number')
                                   ->orWhere('card_number', '=', '');
                            })
                            ->whereNotNull('card_number_in_set')
                            ->where('card_number_in_set', '!=', '')
                            ->whereRaw(
                                "REGEXP_SUBSTR(
                                    CASE 
                                        WHEN LOCATE('/', card_number_in_set) > 0 
                                        THEN SUBSTRING_INDEX(card_number_in_set, '/', 1)
                                        ELSE card_number_in_set
                                    END,
                                    '^[0-9]+'
                                ) IS NOT NULL"
                            );
                            
                            if (isset($filters['numbered_min']) && !empty($filters['numbered_min'])) {
                                $numberedMin = (int)$filters['numbered_min'];
                                $q2->whereRaw(
                                    "CAST(
                                        REGEXP_SUBSTR(
                                            CASE 
                                                WHEN LOCATE('/', card_number_in_set) > 0 
                                                THEN SUBSTRING_INDEX(card_number_in_set, '/', 1)
                                                ELSE card_number_in_set
                                            END,
                                            '^[0-9]+'
                                        ) AS UNSIGNED
                                    ) >= ?",
                                    [$numberedMin]
                                );
                            }
                            
                            if (isset($filters['numbered_max']) && !empty($filters['numbered_max'])) {
                                $numberedMax = (int)$filters['numbered_max'];
                                $q2->whereRaw(
                                    "CAST(
                                        REGEXP_SUBSTR(
                                            CASE 
                                                WHEN LOCATE('/', card_number_in_set) > 0 
                                                THEN SUBSTRING_INDEX(card_number_in_set, '/', 1)
                                                ELSE card_number_in_set
                                            END,
                                            '^[0-9]+'
                                        ) AS UNSIGNED
                                    ) <= ?",
                                    [$numberedMax]
                                );
                            }
                        });
                    });
                });
            }

            // Filtri extra
            if (isset($filters['autograph']) && $filters['autograph'] !== '') {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    if ($filters['autograph'] === 'yes') {
                        $q->where('is_autograph', true);
                    } elseif ($filters['autograph'] === 'no') {
                        $q->where('is_autograph', false);
                    }
                });
            }

            if (isset($filters['relic']) && $filters['relic'] !== '') {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    if ($filters['relic'] === 'yes') {
                        $q->where('is_relic', true);
                    } elseif ($filters['relic'] === 'no') {
                        $q->where('is_relic', false);
                    }
                });
            }

            if (isset($filters['rookie']) && $filters['rookie'] !== '') {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    if ($filters['rookie'] === 'yes') {
                        $q->where('is_rookie', true);
                    } elseif ($filters['rookie'] === 'no') {
                        $q->where('is_rookie', false);
                    }
                });
            }

            if (isset($filters['onCardAuto']) && $filters['onCardAuto'] !== '') {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    if ($filters['onCardAuto'] === 'yes') {
                        $q->where('is_on_card_auto', true);
                    } elseif ($filters['onCardAuto'] === 'no') {
                        $q->where('is_on_card_auto', false);
                    }
                });
            }

            if (isset($filters['jewel']) && $filters['jewel'] !== '') {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    if ($filters['jewel'] === 'yes') {
                        $q->where('is_jewel', true);
                    } elseif ($filters['jewel'] === 'no') {
                        $q->where('is_jewel', false);
                    }
                });
            }

            // Filtro booklet (yes/no) - separato da multi player
            if (isset($filters['booklet']) && $filters['booklet'] !== '') {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    if ($filters['booklet'] === 'yes') {
                        $q->where('is_booklet', true);
                    } elseif ($filters['booklet'] === 'no') {
                        $q->where('is_booklet', false);
                    }
                });
            }

            // Filtri multi player (dual, triple, quad) - booklet rimosso perché è un filtro separato
            if (isset($filters['multi_player']) && is_array($filters['multi_player']) && !empty($filters['multi_player'])) {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    $q->where(function($subQ) use ($filters) {
                        $first = true;
                        foreach ($filters['multi_player'] as $multiPlayerType) {
                            switch ($multiPlayerType) {
                                case 'dual':
                                    if ($first) {
                                        $subQ->where('is_multi_player_dual', true);
                                        $first = false;
                                    } else {
                                        $subQ->orWhere('is_multi_player_dual', true);
                                    }
                                    break;
                                case 'triple':
                                    if ($first) {
                                        $subQ->where('is_multi_player_triple', true);
                                        $first = false;
                                    } else {
                                        $subQ->orWhere('is_multi_player_triple', true);
                                    }
                                    break;
                                case 'quad':
                                    if ($first) {
                                        $subQ->where('is_multi_player_quad', true);
                                        $first = false;
                                    } else {
                                        $subQ->orWhere('is_multi_player_quad', true);
                                    }
                                    break;
                            }
                        }
                    });
                });
            }

            // Filtri multi autograph (dual, triple, quad) - booklet rimosso perché è un filtro separato
            // Nota: multi autograph usa gli stessi campi di multi player
            if (isset($filters['multi_autograph']) && is_array($filters['multi_autograph']) && !empty($filters['multi_autograph'])) {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    $q->where(function($subQ) use ($filters) {
                        $first = true;
                        foreach ($filters['multi_autograph'] as $multiAutographType) {
                            switch ($multiAutographType) {
                                case 'dual':
                                    if ($first) {
                                        $subQ->where('is_multi_player_dual', true);
                                        $first = false;
                                    } else {
                                        $subQ->orWhere('is_multi_player_dual', true);
                                    }
                                    break;
                                case 'triple':
                                    if ($first) {
                                        $subQ->where('is_multi_player_triple', true);
                                        $first = false;
                                    } else {
                                        $subQ->orWhere('is_multi_player_triple', true);
                                    }
                                    break;
                                case 'quad':
                                    if ($first) {
                                        $subQ->where('is_multi_player_quad', true);
                                        $first = false;
                                    } else {
                                        $subQ->orWhere('is_multi_player_quad', true);
                                    }
                                    break;
                            }
                        }
                    });
                });
            }

            // Filtri grading - dalla CardListing (non dal CardModel!)
            if (isset($filters['grading']) && $filters['grading'] !== '') {
                if ($filters['grading'] === 'yes') {
                    $query->whereNotNull('grading_company_id');
                } elseif ($filters['grading'] === 'no') {
                    $query->whereNull('grading_company_id');
                }
            }

            if (isset($filters['grading_score_min']) && !empty($filters['grading_score_min'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('card_condition_score', '>=', $filters['grading_score_min'])
                      ->orWhere('autograph_condition_score', '>=', $filters['grading_score_min']);
                });
            }

            if (isset($filters['grading_score_max']) && !empty($filters['grading_score_max'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('card_condition_score', '<=', $filters['grading_score_max'])
                      ->orWhere('autograph_condition_score', '<=', $filters['grading_score_max']);
                });
            }
        }
        
        // Filtri comuni a tutte le sottocategorie: Set, Year, Brand
        if (isset($filters['set_id']) && !empty($filters['set_id'])) {
            $query->whereHas('cardModel', function($q) use ($filters) {
                $q->where('card_set_id', $filters['set_id']);
            });
        }

        if (isset($filters['year']) && !empty($filters['year'])) {
            $query->whereHas('cardModel', function($q) use ($filters) {
                $q->where('year', $filters['year']);
            });
        }

        if (isset($filters['brand']) && !empty($filters['brand'])) {
            $query->whereHas('cardModel.cardSet', function($q) use ($filters) {
                $q->where('brand', $filters['brand']);
            });
        }

        // Filtro per sottocategoria (singles, sealed-packs, sealed-boxes, lot)
        if (isset($filters['subcategory']) && !empty($filters['subcategory'])) {
            $subcategory = $filters['subcategory'];
            
            switch ($subcategory) {
                case 'singles':
                    // Carte singole: listing_type = 'single' o 'bulk' (per retrocompatibilità)
                    $query->where(function($q) {
                        $q->where('listing_type', 'single')
                          ->orWhere('listing_type', 'bulk')
                          ->orWhereNull('listing_type'); // Retrocompatibilità con inserzioni esistenti
                    });
                    break;
                    
                case 'sealed-packs':
                    // Buste sigillate: listing_type = 'sealed-pack' e card_model_id è NULL
                    $query->where('listing_type', 'sealed-pack')
                          ->whereNull('card_model_id');
                    break;
                    
                case 'sealed-boxes':
                    // Scatole sigillate: listing_type = 'sealed-box' e card_model_id è NULL
                    $query->where('listing_type', 'sealed-box')
                          ->whereNull('card_model_id');
                    break;
                    
                case 'lot':
                    // Lotti: listing_type = 'lot' e card_model_id è NULL
                    $query->where('listing_type', 'lot')
                          ->whereNull('card_model_id');
                    break;
            }
        }

        if (isset($filters['grading_companies']) && is_array($filters['grading_companies']) && !empty($filters['grading_companies'])) {
            // Filtro grading companies dalla CardListing (non dal CardModel!)
            $query->whereIn('grading_company_id', $filters['grading_companies']);
        }

        // Filtri condition - dalla tabella card_listings
        if (isset($filters['conditions']) && is_array($filters['conditions']) && !empty($filters['conditions'])) {
            $query->whereIn('condition', $filters['conditions']);
        }

        // Ordinamento
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        // Validazione dell'ordinamento
        $allowedSortFields = ['price', 'condition', 'quantity', 'created_at', 'updated_at'];
        $allowedSortOrders = ['asc', 'desc'];
        
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, $allowedSortOrders)) {
            $sortOrder = 'desc';
        }
        
        // Applica l'ordinamento
        $query->orderBy($sortBy, $sortOrder);
        
        // Ordinamento secondario per garantire consistenza
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        // Paginazione
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;
        
        $listings = $query->paginate($perPage, ['*'], 'page', $page);

        // Trasforma i dati per il frontend usando le CardListing
        $transformedProducts = $listings->map(function($listing) {
            $cardModel = $listing->cardModel;
            
            // Se cardModel è null, salta questa listing
            if (!$cardModel) {
                return null;
            }
            
            // Priorità alle immagini reali dalle CardListing (caricate dai giocatori)
            $imageUrl = null;
            if ($listing->images && is_array($listing->images) && count($listing->images) > 0) {
                // Prendi la prima immagine dalla CardListing
                $firstImage = $listing->images[0];
                // Se l'immagine non ha già il prefisso /storage/, aggiungilo
                if (!str_starts_with($firstImage, '/storage/') && !str_starts_with($firstImage, 'http')) {
                    $imageUrl = '/storage/' . $firstImage;
                } else {
                    $imageUrl = $firstImage;
                }
            } elseif ($cardModel->image_url) {
                // Fallback all'immagine del CardModel solo se non ci sono immagini nella CardListing
                $imageUrl = $cardModel->image_url;
            }
            
            return [
                'id' => $cardModel->id, // ID del CardModel per compatibilità con il frontend
                'listing_id' => $listing->id, // ID della CardListing
                'name' => $cardModel->player->name ?? 'Unknown Player',
                'team' => $cardModel->team->name ?? 'Unknown Team',
                'set' => $cardModel->cardSet->name ?? 'Unknown Set',
                'year' => $cardModel->year,
                'rarity' => $cardModel->rarity,
                'condition' => $listing->condition ?? 'excellent',
                'price' => number_format($listing->price ?? 0, 2, ',', '.'), // Formato italiano: punto per migliaia, virgola per decimali
                'quantity' => $listing->quantity ?? 1,
                'card_number_in_set' => $cardModel->card_number_in_set,
                'card_number' => $cardModel->card_number,
                'is_rookie' => $cardModel->is_rookie ?? false,
                'is_autograph' => $cardModel->is_autograph ?? false,
                'is_relic' => $cardModel->is_relic ?? false,
                'is_star' => $cardModel->is_star ?? false,
                'is_legend' => $cardModel->is_legend ?? false,
                'imageUrl' => $imageUrl,
                'images' => $listing->images ?? [], // Array completo delle immagini dalla CardListing
                'playerId' => $cardModel->player_id,
                'teamId' => $cardModel->team_id,
                'setId' => $cardModel->card_set_id,
                'brand' => $cardModel->cardSet->brand ?? null,
                'hasAutograph' => $cardModel->is_autograph ?? false,
                'hasRelic' => $cardModel->is_relic ?? false,
                // Dati di grading dalla CardListing (non dal CardModel!)
                'grading_company_id' => $listing->grading_company_id ?? null,
                'grading_company' => $listing->gradingCompany ? [
                    'id' => $listing->gradingCompany->id,
                    'name' => $listing->gradingCompany->name,
                    'slug' => $listing->gradingCompany->slug,
                ] : null,
                'card_condition_score' => $listing->card_condition_score ?? null,
                'autograph_condition_score' => $listing->autograph_condition_score ?? null,
                // Manteniamo per retrocompatibilità (ma questi sono del CardModel, non della CardListing)
                'gradingScore' => $cardModel->grading_score,
                'gradingCompany' => $cardModel->gradingCompany->name ?? null
            ];
        })->filter(); // Rimuove valori null
        
        return response()->json([
            'data' => $transformedProducts,
            'current_page' => $listings->currentPage(),
            'last_page' => $listings->lastPage(),
            'per_page' => $listings->perPage(),
            'total' => $listings->total(),
            'has_more_pages' => $listings->hasMorePages()
        ]);
        } catch (\Exception $e) {
            Log::error('Errore in getFilteredProducts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters
            ]);
            
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 20,
                'total' => 0,
                'has_more_pages' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Errore nel caricamento prodotti'
            ], 500);
        }
    }

    /**
     * Get all card listings for sale filtered by player name
     * Used for /top/football/{player-name} pages
     */
    public function getListingsByPlayerName(Request $request, $playerName)
    {
        try {
            // Decodifica il nome del giocatore dall'URL slug
            $decodedName = str_replace('-', ' ', urldecode($playerName));
            $decodedName = ucwords($decodedName); // Capitalizza la prima lettera di ogni parola
            
            // Cerca tutti i giocatori con questo nome (potrebbero esserci più varianti)
            $players = Player::where('name', 'LIKE', "%{$decodedName}%")
                ->orWhere('name', 'LIKE', "%{$playerName}%")
                ->get();
            
            if ($players->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                    'has_more_pages' => false,
                    'player_name' => $decodedName
                ]);
            }
            
            // Ottieni tutti gli ID dei giocatori trovati
            $playerIds = $players->pluck('id')->toArray();
            
            // Usa la stessa logica di getFilteredProducts ma filtra per player_id
            $filters = $request->all();
            $filters['player_id'] = $playerIds;
            
            // Crea una nuova request con i filtri aggiornati
            // Usa l'URL base per i filtri (l'URL esatto non è importante, servono solo i parametri)
            $baseUrl = url('/api/football/filters/products');
            $newRequest = Request::create($baseUrl, 'GET', $filters);
            $newRequest->headers->add($request->headers->all());
            
            // Chiama getFilteredProducts con i filtri per player
            return $this->getFilteredProducts($newRequest);
            
        } catch (\Exception $e) {
            Log::error('Errore in getListingsByPlayerName', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'player_name' => $playerName
            ]);
            
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 20,
                'total' => 0,
                'has_more_pages' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Errore nel caricamento prodotti'
            ], 500);
        }
    }
}
