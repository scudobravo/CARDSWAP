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

class FootballFilterController extends Controller
{
    /**
     * Get all available filter options for football cards
     */
    public function getFilterOptions()
    {
        $filters = [
            'leagues' => League::active()->ordered()->get(['id', 'name', 'slug', 'country']),
            'teams' => Team::active()->ordered()->get(['id', 'name', 'slug', 'city', 'league_id']),
            'players' => Player::active()->ordered()->get(['id', 'name', 'slug', 'position', 'nationality', 'team_id']),
            'card_sets' => CardSet::active()->ordered()->get(['id', 'name', 'slug', 'brand', 'year', 'season']),
            'grading_companies' => GradingCompany::active()->ordered()->get(['id', 'name', 'slug']),
            'grading_scores' => GradingScore::active()->ordered()->get(['id', 'score', 'description', 'short_code', 'is_special', 'grading_company_id']),
            'positions' => ['Attaccante', 'Centrocampista', 'Difensore', 'Portiere'],
            'rarities' => ['common', 'uncommon', 'rare', 'mythic', 'special'],
            'conditions' => ['mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor', 'fair', 'very_good'],
            'years' => range(1990, date('Y') + 1),
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
            'year' => 'nullable|integer',
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
        
        $players = $playersQuery->limit(50)->get(['id', 'name', 'slug', 'position', 'nationality']);

        return response()->json(['players' => $players]);
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
            'year' => 'nullable|integer',
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
                    $q->where('player_id', $request->player_id);
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
     * Search card sets with autocomplete (minimum 2 characters)
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

        $query = $request->get('q');
        
        // Filtra solo i set che hanno carte Football (slug = calcio)
        $cardSetsQuery = CardSet::whereHas('cardModels', function($q) use ($request) {
                $q->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'calcio');
                });
                
                // Applica filtri aggiuntivi per limitare i risultati
                if ($request->filled('player_id')) {
                    $q->where('player_id', $request->player_id);
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
        $player = Player::find($id);
        
        if (!$player) {
            return response()->json([
                'error' => 'Player not found'
            ], 404);
        }

        return response()->json([
            'player' => $player
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
     * Get chained filter options based on current selections
     */
    public function getChainedFilters(Request $request)
    {
        $filters = $request->all();
        $response = [];

        // Base query per card_models
        $cardModelsQuery = \App\Models\CardModel::query();

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

        // 1. TEAMS - Dipende da Player selezionato
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            $response['teams'] = Team::whereHas('players', function($query) use ($filters) {
                $query->where('id', $filters['player_id']);
            })
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'city']);
        }

        // 2. SETS - Dipende da Player e/o Team
        $setsQuery = CardSet::query();
        if (isset($filters['player_id']) || isset($filters['team_id'])) {
            $setsQuery->whereHas('cardModels', function($query) use ($filters) {
                if (isset($filters['player_id']) && !empty($filters['player_id'])) {
                    $query->where('player_id', $filters['player_id']);
                }
                if (isset($filters['team_id']) && !empty($filters['team_id'])) {
                    $query->where('team_id', $filters['team_id']);
                }
            });
        }
        $response['card_sets'] = $setsQuery->active()->ordered()->get(['id', 'name', 'slug', 'brand', 'year']);

        // 3. YEARS - Dipende da Player, Team, Set
        $yearsQuery = $cardModelsQuery->clone();
        $response['years'] = $yearsQuery->select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // 4. BRANDS - Dipende da Player, Team, Set, Year
        $brandsQuery = $cardModelsQuery->clone();
        $response['brands'] = $brandsQuery->whereHas('cardSet')
            ->with('cardSet:id,brand')
            ->get()
            ->pluck('cardSet.brand')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // 5. RARITIES - Dipende da tutti i filtri precedenti
        $raritiesQuery = $cardModelsQuery->clone();
        $response['rarities'] = $raritiesQuery->select('rarity')
            ->whereNotNull('rarity')
            ->distinct()
            ->orderBy('rarity')
            ->pluck('rarity')
            ->toArray();

        // 6. NUMBERED RANGE - Dipende da tutti i filtri precedenti
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
    }

    /**
     * Get advanced filter options with dependencies (legacy)
     */
    public function getAdvancedFilters(Request $request)
    {
        // Redirect to chained filters for consistency
        return $this->getChainedFilters($request);
    }

    /**
     * Get filtered products based on filter criteria
     */
    public function getFilteredProducts(Request $request)
    {
        $filters = $request->all();
        
        // Determina se la sottocategoria è sealed (packs o boxes)
        $isSealed = isset($filters['subcategory']) && 
                    in_array($filters['subcategory'], ['sealed-packs', 'sealed-boxes']);
        
        // Base query per card_models con join per ordinamento
        $query = CardModel::with(['player', 'team', 'cardSet', 'gradingCompany', 'cardListings'])
            ->leftJoin('players', 'card_models.player_id', '=', 'players.id')
            ->leftJoin('teams', 'card_models.team_id', '=', 'teams.id')
            ->leftJoin('card_listings', function($join) {
                $join->on('card_models.id', '=', 'card_listings.card_model_id')
                     ->where('card_listings.status', '=', 'active');
            })
            ->select('card_models.*', 'card_listings.price as listing_price', 'card_listings.condition as listing_condition')
            ->where('card_models.is_active', true);

        // Per sealed packs/boxes, applica solo Set, Year e Brand
        // Per altri, applica tutti i filtri disponibili
        if (!$isSealed) {
            // Applica filtri a catena: Player → Team → Set → Year → Brand → Rarity
            if (isset($filters['player_id']) && !empty($filters['player_id'])) {
                if (is_array($filters['player_id'])) {
                    $query->whereIn('card_models.player_id', $filters['player_id']);
                } else {
                    $query->where('card_models.player_id', $filters['player_id']);
                }
            }

            if (isset($filters['team_id']) && !empty($filters['team_id'])) {
                $query->where('card_models.team_id', $filters['team_id']);
            }

            if (isset($filters['rarity']) && !empty($filters['rarity'])) {
                $query->where('card_models.rarity', $filters['rarity']);
            }

            // Filtro per range numerato
            if (isset($filters['numbered_min']) && !empty($filters['numbered_min'])) {
                $query->whereRaw("CAST(SUBSTRING_INDEX(card_models.card_number_in_set, '/', 1) AS UNSIGNED) >= ?", [$filters['numbered_min']]);
            }

            if (isset($filters['numbered_max']) && !empty($filters['numbered_max'])) {
                $query->whereRaw("CAST(SUBSTRING_INDEX(card_models.card_number_in_set, '/', 1) AS UNSIGNED) <= ?", [$filters['numbered_max']]);
            }

            // Filtri extra - per ora commentati perché i campi non esistono nella tabella
            // TODO: Implementare questi filtri quando i campi saranno disponibili
            /*
            if (isset($filters['autograph']) && $filters['autograph'] !== '') {
                if ($filters['autograph'] === 'yes') {
                    $query->where('card_models.has_autograph', true);
                } elseif ($filters['autograph'] === 'no') {
                    $query->where('card_models.has_autograph', false);
                }
            }

            if (isset($filters['relic']) && $filters['relic'] !== '') {
                if ($filters['relic'] === 'yes') {
                    $query->where('card_models.has_relic', true);
                } elseif ($filters['relic'] === 'no') {
                    $query->where('card_models.has_relic', false);
                }
            }
            */

            if (isset($filters['rookie']) && $filters['rookie'] !== '') {
                if ($filters['rookie'] === 'yes') {
                    $query->where('card_models.is_rookie', true);
                } elseif ($filters['rookie'] === 'no') {
                    $query->where('card_models.is_rookie', false);
                }
            }

            // Filtri grading
            if (isset($filters['grading']) && $filters['grading'] !== '') {
                if ($filters['grading'] === 'yes') {
                    $query->whereNotNull('card_models.grading_company_id');
                } elseif ($filters['grading'] === 'no') {
                    $query->whereNull('card_models.grading_company_id');
                }
            }

            if (isset($filters['grading_score_min']) && !empty($filters['grading_score_min'])) {
                $query->where('card_models.grading_score', '>=', $filters['grading_score_min']);
            }

            if (isset($filters['grading_score_max']) && !empty($filters['grading_score_max'])) {
                $query->where('card_models.grading_score', '<=', $filters['grading_score_max']);
            }
        }
        
        // Filtri comuni a tutte le sottocategorie: Set, Year, Brand
        if (isset($filters['set_id']) && !empty($filters['set_id'])) {
            $query->where('card_models.card_set_id', $filters['set_id']);
        }

        if (isset($filters['year']) && !empty($filters['year'])) {
            $query->where('card_models.year', $filters['year']);
        }

        if (isset($filters['brand']) && !empty($filters['brand'])) {
            $query->whereHas('cardSet', function($q) use ($filters) {
                $q->where('brand', $filters['brand']);
            });
        }

        // Filtro per sottocategoria (singles, sealed-packs, sealed-boxes, lot)
        // NOTA: Questo filtro è basato su card_listings. Se non ci sono listings, il filtro viene ignorato
        if (isset($filters['subcategory']) && !empty($filters['subcategory'])) {
            $subcategory = $filters['subcategory'];
            
            // Verifica se esistono card_listings nel database
            $hasListings = \App\Models\CardModel::whereHas('cardListings')->exists();
            
            // Applica il filtro solo se ci sono listings
            if ($hasListings) {
                switch ($subcategory) {
                    case 'singles':
                        // Carte singole: quantity = 1, non sealed
                        $query->whereHas('cardListings', function($q) {
                            $q->where('quantity', 1)
                              ->where('is_limited', false);
                        });
                        break;
                        
                    case 'sealed-packs':
                        // Buste sigillate: quantity > 1, sealed
                        $query->whereHas('cardListings', function($q) {
                            $q->where('quantity', '>', 1)
                              ->where('quantity', '<=', 20) // Range ragionevole per buste
                              ->where('is_limited', true);
                        });
                        break;
                        
                    case 'sealed-boxes':
                        // Scatole sigillate: quantity molto alta, sealed
                        $query->whereHas('cardListings', function($q) {
                            $q->where('quantity', '>', 20) // Molte carte = scatola
                              ->where('is_limited', true);
                        });
                        break;
                        
                    case 'lot':
                        // Lotti: quantity > 1, non sealed
                        $query->whereHas('cardListings', function($q) {
                            $q->where('quantity', '>', 1)
                              ->where('is_limited', false);
                        });
                        break;
                }
            }
            // Se non ci sono listings, il filtro subcategory viene ignorato
            // e vengono restituiti tutti i card_models che matchano gli altri filtri
        }

        if (isset($filters['grading_companies']) && is_array($filters['grading_companies']) && !empty($filters['grading_companies'])) {
            $query->whereIn('card_models.grading_company_id', $filters['grading_companies']);
        }

        // Filtri condition - ora dalla tabella card_listings
        if (isset($filters['conditions']) && is_array($filters['conditions']) && !empty($filters['conditions'])) {
            $query->whereIn('card_listings.condition', $filters['conditions']);
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
        
        // Applica l'ordinamento con mapping dei campi
        switch ($sortBy) {
            case 'price':
                $query->orderBy('card_models.price', $sortOrder);
                break;
            case 'player_name':
                $query->orderBy('players.name', $sortOrder);
                break;
            case 'team_name':
                $query->orderBy('teams.name', $sortOrder);
                break;
            default:
                $query->orderBy('card_models.' . $sortBy, $sortOrder);
                break;
        }
        
        // Ordinamento secondario per garantire consistenza
        if ($sortBy !== 'id') {
            $query->orderBy('card_models.id', 'desc');
        }

        // Paginazione
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;
        
        $products = $query->paginate($perPage, ['*'], 'page', $page);

        // Trasforma i dati per il frontend
        $transformedProducts = $products->map(function($cardModel) {
            return [
                'id' => $cardModel->id,
                'name' => $cardModel->player->name ?? 'Unknown Player',
                'team' => $cardModel->team->name ?? 'Unknown Team',
                'set' => $cardModel->cardSet->name ?? 'Unknown Set',
                'year' => $cardModel->year,
                'rarity' => $cardModel->rarity,
                'condition' => 'excellent', // Default condition since we don't have card_listings yet
                'price' => number_format($cardModel->price ?? 0, 2),
                'limitedEdition' => $cardModel->card_number_in_set,
                'isRookie' => $cardModel->is_rookie ?? false,
                'imageUrl' => $cardModel->image_url,
                'playerId' => $cardModel->player_id,
                'teamId' => $cardModel->team_id,
                'setId' => $cardModel->card_set_id,
                'brand' => $cardModel->cardSet->brand ?? null,
                'hasAutograph' => false, // TODO: Implementare quando il campo sarà disponibile
                'hasRelic' => false, // TODO: Implementare quando il campo sarà disponibile
                'gradingScore' => $cardModel->grading_score,
                'gradingCompany' => $cardModel->gradingCompany->name ?? null
            ];
        });

        return response()->json([
            'data' => $transformedProducts,
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'has_more_pages' => $products->hasMorePages()
        ]);
    }
}
