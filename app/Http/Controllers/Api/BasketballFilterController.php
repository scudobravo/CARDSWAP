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

class BasketballFilterController extends Controller
{
    /**
     * Get all available filter options for basketball cards
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
            'positions' => ['Point Guard', 'Shooting Guard', 'Small Forward', 'Power Forward', 'Center'],
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
            'year' => 'nullable|integer',
            'brand' => 'nullable|string'
        ]);
        
        $query = $request->get('q', '');
        
        // Filtra solo i player che hanno carte Basketball
        // NON carichiamo la relazione 'team' per rendere il giocatore indipendente
        $playersQuery = Player::whereHas('cardModels', function($q) use ($request) {
                $q->whereHas('category', function($catQuery) {
                    $catQuery->where('slug', 'basketball');
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
            $query->where('year', $filters['year']);
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
            $teams = Team::whereIn('id', function($query) use ($filters) {
                $query->select('team_id')
                    ->from('card_models')
                    ->whereIn('player_id', is_array($filters['player_id']) ? $filters['player_id'] : [$filters['player_id']])
                    ->where('is_active', true);
            })->active()->ordered()->get(['id', 'name', 'slug']);
            $result['teams'] = $teams;
        }

        // Sets disponibili
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            $sets = CardSet::whereIn('id', function($query) use ($filters) {
                $query->select('card_set_id')
                    ->from('card_models')
                    ->whereIn('player_id', is_array($filters['player_id']) ? $filters['player_id'] : [$filters['player_id']])
                    ->where('is_active', true);
            })->active()->ordered()->get(['id', 'name', 'slug', 'brand', 'year']);
            $result['sets'] = $sets;
        }

        // Years disponibili
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            $years = CardModel::whereIn('player_id', is_array($filters['player_id']) ? $filters['player_id'] : [$filters['player_id']])
                ->where('is_active', true)
                ->distinct()
                ->pluck('year')
                ->sort()
                ->values();
            $result['years'] = $years;
        }

        // Brands disponibili
        if (isset($filters['player_id']) && !empty($filters['player_id'])) {
            $brands = CardSet::whereIn('id', function($query) use ($filters) {
                $query->select('card_set_id')
                    ->from('card_models')
                    ->whereIn('player_id', is_array($filters['player_id']) ? $filters['player_id'] : [$filters['player_id']])
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
            $rarities = CardModel::whereIn('player_id', is_array($filters['player_id']) ? $filters['player_id'] : [$filters['player_id']])
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
            $query->where('year', $filters['year']);
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
        if (isset($filters['numbered_min']) && !empty($filters['numbered_min'])) {
            $query->where('card_number_in_set', '>=', $filters['numbered_min']);
        }

        if (isset($filters['numbered_max']) && !empty($filters['numbered_max'])) {
            $query->where('card_number_in_set', '<=', $filters['numbered_max']);
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
        
        // Base query per card_models con join per ordinamento
        $query = CardModel::with(['player', 'team', 'cardSet', 'gradingCompany'])
            ->leftJoin('players', 'card_models.player_id', '=', 'players.id')
            ->leftJoin('teams', 'card_models.team_id', '=', 'teams.id')
            ->select('card_models.*')
            ->where('card_models.is_active', true)
            ->whereHas('category', function($query) {
                $query->where('slug', 'basketball');
            });

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

            // Filtri per numbered
            if (isset($filters['numbered_min']) && !empty($filters['numbered_min'])) {
                $query->where('card_models.card_number_in_set', '>=', $filters['numbered_min']);
            }

            if (isset($filters['numbered_max']) && !empty($filters['numbered_max'])) {
                $query->where('card_models.card_number_in_set', '<=', $filters['numbered_max']);
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
