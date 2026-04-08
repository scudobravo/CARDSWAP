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
use App\Support\PlayerSearchQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BasketballFilterController extends Controller
{
    /**
     * Get all available filter options for basketball cards
     */
    public function getFilterOptions()
    {
        // Rarities dinamiche dal DB: SOLO rarità base (senza variation)
        $dynamicRarities = CardModel::whereHas('category', function($q) {
                $q->where('slug', 'basketball');
            })
            ->where('is_active', true)
            ->whereNotNull('rarity')
            ->where('rarity', '!=', '')
            ->distinct()
            ->orderBy('rarity')
            ->pluck('rarity')
            ->toArray();

        $filters = [
            'leagues' => League::active()->ordered()->get(['id', 'name', 'slug', 'country']),
            'teams' => Team::active()->ordered()->get(['id', 'name', 'slug', 'city', 'league_id']),
            'players' => Player::active()->ordered()->get(['id', 'name', 'slug', 'position', 'nationality', 'team_id']),
            'card_sets' => CardSet::active()->ordered()->get(['id', 'name', 'slug', 'brand', 'year', 'season']),
            'grading_companies' => GradingCompany::active()->ordered()->get(['id', 'name', 'slug']),
            'grading_scores' => GradingScore::active()->ordered()->get(['id', 'score', 'description', 'short_code', 'is_special', 'grading_company_id']),
            'positions' => ['Point Guard', 'Shooting Guard', 'Small Forward', 'Power Forward', 'Center'],
            'rarities' => $dynamicRarities,
            'conditions' => ['mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor', 'fair', 'very_good'],
            'years' => array_reverse(range(1990, date('Y') + 1)),
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

        return response()->json($teams);
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

        return response()->json($players);
    }

    /**
     * Get players filtered by league
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

        return response()->json($players);
    }

    /**
     * Search players with autocomplete
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
        
        // Filtra solo i player che hanno carte Basketball
        $playersQuery = Player::whereHas('cardModels', function($q) use ($request) {
                $q->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'basketball');
                });
                
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
            ->active();

        if (!empty($query)) {
            PlayerSearchQuery::wherePlayerNameMatches($playersQuery, 'name', $query);
        }

        PlayerSearchQuery::orderPlayerNameForAutocomplete($playersQuery, $query);

        $players = $playersQuery
            ->limit(200)
            ->get(['id', 'name', 'slug', 'position', 'nationality']);

        $transformedPlayers = $players->map(function($player) {
            return [
                'id' => $player->id,
                'name' => $player->name,
                'slug' => $player->slug,
                'position' => $player->position,
                'nationality' => $player->nationality,
                'display_name' => $player->name,
            ];
        })->values();

        return response()->json(['players' => $transformedPlayers]);
    }

    /**
     * Search teams with autocomplete
     */
    public function searchTeams(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'player_id' => 'nullable|integer',
            'set_id' => 'nullable|integer',
            'year' => 'nullable|integer',
            'brand' => 'nullable|string'
        ]);
        
        $query = $request->get('q', '');
        
        // Filtra solo i team che hanno carte Basketball
        $teamsQuery = Team::with('league')
            ->whereHas('cardModels', function($q) use ($request) {
                $q->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'basketball');
                });
                
                // Applica filtri aggiuntivi per limitare i risultati
                if ($request->filled('player_id')) {
                    // Se è un singolo player_id, cerca tutte le carte di tutti i giocatori con lo stesso nome
                    $player = Player::find($request->player_id);
                    if ($player) {
                        $playerIds = Player::where('name', $player->name)->pluck('id')->toArray();
                        $q->whereIn('player_id', $playerIds);
                    } else {
                        $q->where('player_id', $request->player_id);
                    }
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
        
        $teams = $teamsQuery->limit(50)->get(['id', 'name', 'slug', 'city', 'league_id']);

        return response()->json(['teams' => $teams]);
    }

    /**
     * Search card sets with autocomplete
     */
    public function searchCardSets(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'player_id' => 'nullable|integer',
            'team_id' => 'nullable|integer',
            'year' => 'nullable|integer',
            'brand' => 'nullable|string'
        ]);
        
        $query = $request->get('q', '');
        
        // Filtra solo i set che hanno carte Basketball
        $cardSetsQuery = CardSet::whereHas('cardModels', function($q) use ($request) {
                $q->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'basketball');
                });
                
                // Applica filtri aggiuntivi per limitare i risultati
                if ($request->filled('player_id')) {
                    // Se è un singolo player_id, cerca tutte le carte di tutti i giocatori con lo stesso nome
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
        
        $cardSets = $cardSetsQuery->limit(50)->get(['id', 'name', 'slug', 'brand', 'year', 'season']);

        return response()->json(['card_sets' => $cardSets]);
    }

    /**
     * Get a single player by ID
     * NON carichiamo la relazione 'team' per mantenere il giocatore indipendente dalla squadra
     */
    public function getPlayerById($id)
    {
        $player = Player::with([
            'team',
            'cardModels' => function($query) {
                $query->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'basketball');
                })->select(
                    'id',
                    'player_id',
                    'card_number',
                    'card_number_in_set',
                    'name',
                    'year',
                    'rarity',
                    'rarity_variation',
                    'is_rookie',
                    'is_autograph',
                    'is_relic',
                    'is_on_card_auto',
                    'is_jewel',
                    'is_booklet',
                    'is_multi_player_dual',
                    'is_multi_player_triple',
                    'is_multi_player_quad',
                    'card_set_id',
                    'team_id'
                )->with(['cardSet:id,name,brand', 'team:id,name,slug']);
            }
        ])->find($id);
        
        if (!$player) {
            return response()->json([
                'error' => 'Player not found'
            ], 404);
        }

        $cardNumbers = $player->cardModels->pluck('card_number')->filter()->unique()->values();
        $cardNumbersInSet = $player->cardModels->pluck('card_number_in_set')->filter()->unique()->values();
        $effectiveCardNumbers = $cardNumbersInSet->count() > 0 ? $cardNumbersInSet : $cardNumbers;
        $allTeams = $player->cardModels->pluck('team')->filter()->unique('id')->values();

        $transformedPlayer = [
            'id' => $player->id,
            'name' => $player->name,
            'slug' => $player->slug,
            'position' => $player->position,
            'nationality' => $player->nationality,
            'team' => $player->team ? [
                'id' => $player->team->id,
                'name' => $player->team->name,
                'slug' => $player->team->slug,
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
                        'brand' => $card->cardSet->brand,
                    ] : null,
                    'team' => $card->team ? [
                        'id' => $card->team->id,
                        'name' => $card->team->name,
                        'slug' => $card->team->slug ?? null,
                    ] : null,
                ];
            })->toArray(),
            'all_teams' => $allTeams->map(function($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->slug ?? null,
                ];
            })->values()->toArray(),
        ];

        return response()->json([
            'player' => $transformedPlayer
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
            ->get(['id', 'name', 'slug', 'brand', 'year']);

        return response()->json($cardSets);
    }

    /**
     * Get card sets filtered by brand
     */
    public function getCardSetsByBrand(Request $request)
    {
        $request->validate([
            'brand' => 'required|string|max:255'
        ]);

        $cardSets = CardSet::where('brand', $request->brand)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'brand', 'year']);

        return response()->json($cardSets);
    }

    /**
     * Get available years for basketball cards
     */
    public function getAvailableYears()
    {
        $years = CardModel::whereHas('category', function($query) {
                $query->where('slug', 'basketball');
            })
            ->where('is_active', true)
            ->distinct()
            ->pluck('year')
            ->sort()
            ->values();

        return response()->json($years);
    }

    /**
     * Get available brands for basketball cards
     */
    public function getAvailableBrands()
    {
        $brands = CardSet::whereHas('category', function($query) {
                $query->where('slug', 'basketball');
            })
            ->where('is_active', true)
            ->distinct()
            ->pluck('brand')
            ->filter()
            ->sort()
            ->values();

        return response()->json($brands);
    }

    /**
     * Get available rarities for basketball cards
     */
    public function getAvailableRarities()
    {
        $rarities = CardModel::whereHas('category', function($query) {
                $query->where('slug', 'basketball');
            })
            ->where('is_active', true)
            ->distinct()
            ->pluck('rarity')
            ->filter()
            ->sort()
            ->values();

        return response()->json($rarities);
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
                $catQuery->where('slug', 'basketball');
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
            $this->applyYearFilter($raritiesQuery, $request->year);
        }
        
        if ($request->filled('brand')) {
            $raritiesQuery->whereHas('cardSet', function($setQuery) use ($request) {
                $setQuery->where('brand', $request->brand);
            });
        }
        
        // Se c'è una query di ricerca, filtra i risultati SOLO sulla colonna rarity
        // NON includere rarity_variation nella ricerca
        if (!empty($query) && strlen($query) >= 1) {
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
        if (!empty($query) && strlen($query) >= 1) {
            $rarities = array_filter($rarities, function($rarity) use ($query) {
                return stripos($rarity, $query) !== false;
            });
            // Riordina dopo il filtro
            sort($rarities);
        }
        
        return response()->json(['rarities' => array_values($rarities)]);
    }

    /**
     * Get grading scores filtered by company
     */
    public function getGradingScoresByCompany(Request $request)
    {
        $request->validate([
            'grading_company_id' => 'required|integer|exists:grading_companies,id'
        ]);

        $scores = GradingScore::where('grading_company_id', $request->grading_company_id)
            ->active()
            ->ordered()
            ->get(['id', 'score', 'description', 'short_code', 'is_special']);

        return response()->json($scores);
    }

    /**
     * Get chained filters for basketball cards
     */
    public function getChainedFilters(Request $request)
    {
        $filters = $request->all();
        
        // Base query per basketball cards
        $query = CardModel::whereHas('category', function($query) {
            $query->where('slug', 'basketball');
        })->where('is_active', true);

        // Applica filtri a catena: Player → Team → Set → Year → Brand → Rarity
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            if (is_array($filters['player_id'])) {
                $query->whereIn('player_id', $filters['player_id']);
            } else {
                $query->where('player_id', $filters['player_id']);
            }
        }

        if (isset($filters['team_id']) && !empty($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (isset($filters['set_id']) && !empty($filters['set_id'])) {
            $query->where('card_set_id', $filters['set_id']);
        }

        if (isset($filters['year']) && !empty($filters['year'])) {
            $this->applyYearFilter($query, $filters['year']);
        }

        if (isset($filters['brand']) && !empty($filters['brand'])) {
            $query->whereHas('cardSet', function($q) use ($filters) {
                $q->where('brand', $filters['brand']);
            });
        }

        // Raccoglie i dati per i filtri successivi
        $result = [];

        // Teams disponibili
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            // Se è un singolo player_id, cerca tutte le squadre delle carte di tutti i giocatori con lo stesso nome
            if (is_array($filters['player_id'])) {
                $playerIds = $filters['player_id'];
            } else {
                $player = Player::find($filters['player_id']);
                if ($player) {
                    $playerIds = Player::where('name', $player->name)->pluck('id')->toArray();
                } else {
                    $playerIds = [$filters['player_id']];
                }
            }
            
            $teams = Team::whereIn('id', function($query) use ($playerIds) {
                $query->select('team_id')
                    ->from('card_models')
                    ->whereIn('player_id', $playerIds)
                    ->where('is_active', true);
            })->active()->ordered()->get(['id', 'name', 'slug']);
            $result['teams'] = $teams;
        }

        // Sets disponibili
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            // Se è un singolo player_id, cerca tutti i set delle carte di tutti i giocatori con lo stesso nome
            if (is_array($filters['player_id'])) {
                $playerIds = $filters['player_id'];
            } else {
                $player = Player::find($filters['player_id']);
                if ($player) {
                    $playerIds = Player::where('name', $player->name)->pluck('id')->toArray();
                } else {
                    $playerIds = [$filters['player_id']];
                }
            }
            
            $sets = CardSet::whereIn('id', function($query) use ($playerIds) {
                $query->select('card_set_id')
                    ->from('card_models')
                    ->whereIn('player_id', $playerIds)
                    ->where('is_active', true);
            })->active()->ordered()->get(['id', 'name', 'slug', 'brand', 'year']);
            $result['sets'] = $sets;
        }

        // Years disponibili
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            // Se è un singolo player_id, cerca tutti gli anni delle carte di tutti i giocatori con lo stesso nome
            if (is_array($filters['player_id'])) {
                $playerIds = $filters['player_id'];
            } else {
                $player = Player::find($filters['player_id']);
                if ($player) {
                    $playerIds = Player::where('name', $player->name)->pluck('id')->toArray();
                } else {
                    $playerIds = [$filters['player_id']];
                }
            }
            
            $years = CardModel::whereIn('player_id', $playerIds)
                ->where('is_active', true)
                ->distinct()
                ->pluck('year')
                ->sort()
                ->values();
            $result['years'] = $years;
        }

        // Brands disponibili
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            // Se è un singolo player_id, cerca tutti i brand delle carte di tutti i giocatori con lo stesso nome
            if (is_array($filters['player_id'])) {
                $playerIds = $filters['player_id'];
            } else {
                $player = Player::find($filters['player_id']);
                if ($player) {
                    $playerIds = Player::where('name', $player->name)->pluck('id')->toArray();
                } else {
                    $playerIds = [$filters['player_id']];
                }
            }
            
            $brands = CardSet::whereIn('id', function($query) use ($playerIds) {
                $query->select('card_set_id')
                    ->from('card_models')
                    ->whereIn('player_id', $playerIds)
                    ->where('is_active', true);
            })->where('is_active', true)
            ->distinct()
            ->pluck('brand')
            ->filter()
            ->sort()
            ->values();
            $result['brands'] = $brands;
        }

        // Rarities disponibili
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            // Se è un singolo player_id, cerca tutte le rarità delle carte di tutti i giocatori con lo stesso nome
            if (is_array($filters['player_id'])) {
                $playerIds = $filters['player_id'];
            } else {
                $player = Player::find($filters['player_id']);
                if ($player) {
                    $playerIds = Player::where('name', $player->name)->pluck('id')->toArray();
                } else {
                    $playerIds = [$filters['player_id']];
                }
            }
            
            $rarities = CardModel::whereIn('player_id', $playerIds)
                ->where('is_active', true)
                ->distinct()
                ->pluck('rarity')
                ->filter()
                ->sort()
                ->values();
            $result['rarities'] = $rarities;
        }

        return response()->json($result);
    }

    /**
     * Get advanced filters for basketball cards
     */
    public function getAdvancedFilters(Request $request)
    {
        $filters = $request->all();
        
        // Base query per basketball cards
        $query = CardModel::whereHas('category', function($query) {
            $query->where('slug', 'basketball');
        })->where('is_active', true);

        // Applica filtri avanzati
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            if (is_array($filters['player_id'])) {
                $query->whereIn('player_id', $filters['player_id']);
            } else {
                $query->where('player_id', $filters['player_id']);
            }
        }

        if (isset($filters['team_id']) && !empty($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (isset($filters['set_id']) && !empty($filters['set_id'])) {
            $query->where('card_set_id', $filters['set_id']);
        }

        if (isset($filters['year']) && !empty($filters['year'])) {
            $this->applyYearFilter($query, $filters['year']);
        }

        if (isset($filters['brand']) && !empty($filters['brand'])) {
            $query->whereHas('cardSet', function($q) use ($filters) {
                $q->where('brand', $filters['brand']);
            });
        }

        if (isset($filters['rarity']) && !empty($filters['rarity'])) {
            $query->where('rarity', $filters['rarity']);
        }

        // Filtri per grading
        if (isset($filters['grading']) && $filters['grading'] !== '') {
            if ($filters['grading'] === 'yes') {
                $query->whereNotNull('grading_company_id');
            } elseif ($filters['grading'] === 'no') {
                $query->whereNull('grading_company_id');
            }
        }

        if (isset($filters['grading_company_id']) && !empty($filters['grading_company_id'])) {
            $query->where('grading_company_id', $filters['grading_company_id']);
        }

        if (isset($filters['grading_score_min']) && !empty($filters['grading_score_min'])) {
            $query->where('grading_score', '>=', $filters['grading_score_min']);
        }

        if (isset($filters['grading_score_max']) && !empty($filters['grading_score_max'])) {
            $query->where('grading_score', '<=', $filters['grading_score_max']);
        }

        // Filtri per rookie
        if (isset($filters['rookie']) && $filters['rookie'] !== '') {
            if ($filters['rookie'] === 'yes') {
                $query->where('is_rookie', true);
            } elseif ($filters['rookie'] === 'no') {
                $query->where('is_rookie', false);
            }
        }

        // Filtri per numbered
        // Filtro per range numerato
        // Usa card_number_in_set e estrae il numero all'inizio della stringa
        // Gestisce formati come: "1", "5/100", "01-gen", "1-AU", "A-01" (estrae solo il numero iniziale)
        if (isset($filters['numbered_min']) && !empty($filters['numbered_min']) || isset($filters['numbered_max']) && !empty($filters['numbered_max'])) {
            $query->whereNotNull('card_number_in_set')
                  ->where('card_number_in_set', '!=', '');
            
            // Estrae solo i numeri all'inizio della stringa (prima di qualsiasi carattere non numerico)
            // Usa REGEXP_SUBSTR per estrarre la sequenza di numeri all'inizio
            // IMPORTANTE: Filtra solo le righe che hanno un numero all'inizio (REGEXP_SUBSTR non NULL)
            $query->whereRaw(
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
                $query->whereRaw(
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
                $query->whereRaw(
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
        }

        $products = $query->with(['player', 'team', 'cardSet', 'gradingCompany'])
            ->paginate(20);

        return response()->json($products);
    }

    /**
     * Get filtered products for basketball cards
     */
    public function getFilteredProducts(Request $request)
    {
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
                'cardSet', // Per sealed-pack/box/lot
                'seller'
            ])
            ->where('status', 'active')
            ->where(function($q) {
                // Per inserzioni con cardModel (singles, bulk)
                $q->whereHas('cardModel', function($cardModelQ) {
                    $cardModelQ->where('is_active', true)
                      ->whereHas('category', function($catQ) {
                          $catQ->where('slug', 'basketball');
                      });
                })
                // Per sealed-pack, sealed-box e lotti (che non hanno cardModel), includili solo se della categoria basketball
                ->orWhere(function($sealedQ) {
                    $sealedQ->where(function($subQ) {
                        $subQ->where('listing_type', 'sealed-pack')
                             ->orWhere('listing_type', 'sealed-box')
                             ->orWhere('listing_type', 'lot');
                    })
                    ->whereNull('card_model_id')
                    ->whereHas('cardSet', function($cardSetQ) {
                        $cardSetQ->whereHas('category', function($catQ) {
                            $catQ->where('slug', 'basketball');
                        });
                    });
                });
            });

        // Per sealed packs/boxes, applica solo Set, Year e Brand
        // Per altri, applica tutti i filtri disponibili
        // IMPORTANTE: I filtri per numerazione, RC, on card, ecc. devono essere applicati SOLO per singles
        // perché usano whereHas('cardModel', ...) che escluderebbe le sealed-pack/box/lot
        $isSingles = !isset($filters['subcategory']) || $filters['subcategory'] === 'singles' || empty($filters['subcategory']);
        
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

            // Filtri per grading
            if (isset($filters['grading']) && $filters['grading'] !== '') {
                if ($filters['grading'] === 'yes') {
                    $query->whereNotNull('card_models.grading_company_id');
                } elseif ($filters['grading'] === 'no') {
                    $query->whereNull('card_models.grading_company_id');
                }
            }

            if (isset($filters['grading_company_id']) && !empty($filters['grading_company_id'])) {
                $query->where('card_models.grading_company_id', $filters['grading_company_id']);
            }

            if (isset($filters['grading_score_min']) && !empty($filters['grading_score_min'])) {
                $query->where('card_models.grading_score', '>=', $filters['grading_score_min']);
            }

            if (isset($filters['grading_score_max']) && !empty($filters['grading_score_max'])) {
                $query->where('card_models.grading_score', '<=', $filters['grading_score_max']);
            }

            // Filtri per rookie
            if (isset($filters['rookie']) && $filters['rookie'] !== '') {
                if ($filters['rookie'] === 'yes') {
                    $query->where('card_models.is_rookie', true);
                } elseif ($filters['rookie'] === 'no') {
                    $query->where('card_models.is_rookie', false);
                }
            }

            // Filtro booklet (yes/no) - separato da multi player
            if (isset($filters['booklet']) && $filters['booklet'] !== '') {
                if ($filters['booklet'] === 'yes') {
                    $query->where('card_models.is_booklet', true);
                } elseif ($filters['booklet'] === 'no') {
                    $query->where('card_models.is_booklet', false);
                }
            }

            // Filtri multi player (dual, triple, quad) - booklet rimosso perché è un filtro separato
            if (isset($filters['multi_player']) && is_array($filters['multi_player']) && !empty($filters['multi_player'])) {
                $query->where(function($subQ) use ($filters) {
                    $first = true;
                    foreach ($filters['multi_player'] as $multiPlayerType) {
                        switch ($multiPlayerType) {
                            case 'dual':
                                if ($first) {
                                    $subQ->where('card_models.is_multi_player_dual', true);
                                    $first = false;
                                } else {
                                    $subQ->orWhere('card_models.is_multi_player_dual', true);
                                }
                                break;
                            case 'triple':
                                if ($first) {
                                    $subQ->where('card_models.is_multi_player_triple', true);
                                    $first = false;
                                } else {
                                    $subQ->orWhere('card_models.is_multi_player_triple', true);
                                }
                                break;
                            case 'quad':
                                if ($first) {
                                    $subQ->where('card_models.is_multi_player_quad', true);
                                    $first = false;
                                } else {
                                    $subQ->orWhere('card_models.is_multi_player_quad', true);
                                }
                                break;
                        }
                    }
                });
            }

            // Filtri multi autograph (dual, triple, quad) - booklet rimosso perché è un filtro separato
            // Nota: multi autograph usa gli stessi campi di multi player
            if (isset($filters['multi_autograph']) && is_array($filters['multi_autograph']) && !empty($filters['multi_autograph'])) {
                $query->where(function($subQ) use ($filters) {
                    $first = true;
                    foreach ($filters['multi_autograph'] as $multiAutographType) {
                        switch ($multiAutographType) {
                            case 'dual':
                                if ($first) {
                                    $subQ->where('card_models.is_multi_player_dual', true);
                                    $first = false;
                                } else {
                                    $subQ->orWhere('card_models.is_multi_player_dual', true);
                                }
                                break;
                            case 'triple':
                                if ($first) {
                                    $subQ->where('card_models.is_multi_player_triple', true);
                                    $first = false;
                                } else {
                                    $subQ->orWhere('card_models.is_multi_player_triple', true);
                                }
                                break;
                            case 'quad':
                                if ($first) {
                                    $subQ->where('card_models.is_multi_player_quad', true);
                                    $first = false;
                                } else {
                                    $subQ->orWhere('card_models.is_multi_player_quad', true);
                                }
                                break;
                        }
                    }
                });
            }

            // Filtri per numbered
            // Filtro per range numerato
            // IMPORTANTE: Usa SOLO card_number_in_set (colonna "NUMBERED /" del CSV)
            // Gestisce formati come: "1", "5", "25", "99", "250" (estrae solo il numero)
            // IMPORTANTE: Applica questo filtro SOLO per singles (non per sealed-pack/box/lot)
            if ($isSingles && (isset($filters['numbered_min']) && !empty($filters['numbered_min']) || isset($filters['numbered_max']) && !empty($filters['numbered_max']))) {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    // Filtra SOLO su card_number_in_set (NUMBERED /)
                    $q->whereNotNull('card_number_in_set')
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
                        $q->whereRaw(
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
                        $q->whereRaw(
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
            }

            // Filtri extra
            if (isset($filters['autograph']) && $filters['autograph'] !== '') {
                if ($filters['autograph'] === 'yes') {
                    $query->where('card_models.is_autograph', true);
                } elseif ($filters['autograph'] === 'no') {
                    $query->where('card_models.is_autograph', false);
                }
            }

            if (isset($filters['relic']) && $filters['relic'] !== '') {
                if ($filters['relic'] === 'yes') {
                    $query->where('card_models.is_relic', true);
                } elseif ($filters['relic'] === 'no') {
                    $query->where('card_models.is_relic', false);
                }
            }

            if (isset($filters['onCardAuto']) && $filters['onCardAuto'] !== '') {
                if ($filters['onCardAuto'] === 'yes') {
                    $query->where('card_models.is_on_card_auto', true);
                } elseif ($filters['onCardAuto'] === 'no') {
                    $query->where('card_models.is_on_card_auto', false);
                }
            }

            if (isset($filters['jewel']) && $filters['jewel'] !== '') {
                if ($filters['jewel'] === 'yes') {
                    $query->where('card_models.is_jewel', true);
                } elseif ($filters['jewel'] === 'no') {
                    $query->where('card_models.is_jewel', false);
                }
            }
        }
        
        // Filtri comuni a tutte le sottocategorie: Set, Year, Brand
        // Per sealed-pack, sealed-box e lot, questi filtri vengono applicati solo se hanno un cardModel
        if (isset($filters['set_id']) && !empty($filters['set_id'])) {
            $query->whereHas('cardModel', function($q) use ($filters) {
                $q->where('card_set_id', $filters['set_id']);
            });
        }

        if (isset($filters['year']) && !empty($filters['year'])) {
            $query->whereHas('cardModel', function($q) use ($filters) {
                $this->applyYearFilter($q, $filters['year'], 'year');
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
                    // Escludi sealed-pack, sealed-box e lot
                    $query->where(function($q) {
                            $q->where(function($subQ) {
                                $subQ->where('listing_type', 'single')
                                     ->orWhere('listing_type', 'bulk')
                                 ->orWhereNull('listing_type'); // Retrocompatibilità con inserzioni esistenti
                        })
                        ->where(function($notSealedQ) {
                            $notSealedQ->where('listing_type', '!=', 'sealed-pack')
                                       ->where('listing_type', '!=', 'sealed-box')
                                       ->where('listing_type', '!=', 'lot')
                                       ->orWhereNull('listing_type');
                            });
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

        // Ordinamento
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        // Validazione dell'ordinamento
        $allowedSortFields = ['price', 'year', 'created_at', 'id', 'player_name', 'team_name'];
        $allowedSortOrders = ['asc', 'desc'];
        
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, $allowedSortOrders)) {
            $sortOrder = 'desc';
        }
        
        // Applica l'ordinamento
        // Validazione dell'ordinamento
        $allowedSortFields = ['price', 'condition', 'quantity', 'created_at', 'updated_at'];
        $allowedSortOrders = ['asc', 'desc'];
        
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, $allowedSortOrders)) {
            $sortOrder = 'desc';
        }
        
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
            
            // Per sealed-pack, sealed-box e lot, cardModel è NULL
            if (!$cardModel) {
                // Gestisci sealed-pack, sealed-box e lot
                $imageUrl = null;
                if ($listing->images && is_array($listing->images) && count($listing->images) > 0) {
                    $firstImage = $listing->images[0];
                    if (!str_starts_with($firstImage, '/storage/') && !str_starts_with($firstImage, 'http')) {
                        $imageUrl = '/storage/' . $firstImage;
                    } else {
                        $imageUrl = $firstImage;
                    }
                }
                
                // Carica il cardSet se presente
                $cardSet = $listing->cardSet;
                
                // Per Lot: usa sempre il title inserito dall'utente se presente, altrimenti default
                // Per sealed-pack/box: usa il nome del set se disponibile, altrimenti il title, altrimenti default
                $displayName = null;
                if ($listing->listing_type === 'lot') {
                    // Per Lot, priorità al title inserito dall'utente
                    $displayName = $listing->title && $listing->title !== 'Carta' ? $listing->title : 'Lot';
                } else {
                    // Per sealed-pack/box, priorità al nome del set
                    if ($cardSet && $cardSet->name) {
                        $displayName = $cardSet->name;
                    } elseif ($listing->title && $listing->title !== 'Carta') {
                        $displayName = $listing->title;
                    } else {
                        $displayName = $listing->listing_type === 'sealed-pack' ? 'Sealed Pack' : 'Sealed Box';
                    }
                }
                
            return [
                    'id' => $listing->id,
                    'listing_id' => $listing->id,
                    'name' => $displayName,
                    'team' => null,
                    'set' => $cardSet->name ?? null,
                    'year' => $listing->year ?? ($cardSet->year ?? null),
                    'rarity' => null,
                    'condition' => $listing->condition ?? 'mint',
                    'price' => number_format($listing->price ?? 0, 2, ',', '.'),
                    'card_number_in_set' => null,
                    'is_rookie' => false,
                    'is_autograph' => false,
                    'is_relic' => false,
                    'is_star' => false,
                    'is_legend' => false,
                    'imageUrl' => $imageUrl,
                    'images' => $listing->images ?? [],
                    'playerId' => null,
                    'teamId' => null,
                    'setId' => $listing->card_set_id,
                    'brand' => $listing->brand ?? ($cardSet->brand ?? null),
                    'hasAutograph' => false,
                    'hasRelic' => false,
                    'gradingScore' => null,
                    'gradingCompany' => null,
                    'listing_type' => $listing->listing_type,
                    'quantity' => $listing->quantity ?? 1,
                    'description' => $listing->description ?? null
                ];
            }
            
            // Per inserzioni con cardModel (singles, bulk)
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
                'rarity_variation' => $cardModel->rarity_variation,
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
    }

    /**
     * Get all card listings for sale filtered by player name
     * Used for /top/basketball/{player-name} pages
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
            $baseUrl = url('/api/basketball/filters/products');
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

    /**
     * Helper method to apply year filter with support for partial year matching
     * (e.g., "2024" matches "2024/25")
     */
    private function applyYearFilter($query, $year, $field = 'year')
    {
        if (empty($year)) {
            return $query;
        }

        // Se l'anno è solo un numero (es. "2024"), cerca anche anni con formato "2024/25"
        if (preg_match('/^\d{4}$/', $year)) {
            $query->where(function($q) use ($year, $field) {
                $q->where($field, $year)
                  ->orWhere($field, 'LIKE', $year . '/%');
            });
        } else {
            $query->where($field, $year);
        }

        return $query;
    }
}
