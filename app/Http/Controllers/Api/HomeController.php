<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CardModel;
use App\Models\CardListing;
use App\Models\CardSet;
use App\Models\League;
use App\Models\Team;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Get homepage data with macro-areas and featured content
     */
    public function index(): JsonResponse
    {
        try {
            return CacheService::rememberMedium(
                CacheService::CACHE_KEYS['homepage_stats'],
                function () {
                    // Categorie principali con conteggio carte
                    $categories = Category::withCount(['cardModels' => function ($query) {
                        $query->where('is_active', true);
                    }])->get();

                    // Set di carte popolari (ultimi 5 anni)
                    $popularSets = CardSet::where('year', '>=', date('Y') - 5)
                        ->withCount(['cardModels' => function ($query) {
                            $query->where('is_active', true);
                        }])
                        ->orderBy('year', 'desc')
                        ->orderBy('card_models_count', 'desc')
                        ->limit(10)
                        ->get();

                    // Leghe popolari
                    $popularLeagues = League::withCount(['teams', 'players'])
                        ->orderBy('teams_count', 'desc')
                        ->orderBy('players_count', 'desc')
                        ->limit(8)
                        ->get();

                    // Squadre più popolari
                    $popularTeams = Team::withCount(['players', 'cardModels'])
                        ->orderBy('card_models_count', 'desc')
                        ->orderBy('players_count', 'desc')
                        ->limit(12)
                        ->get();

                    // Carte in evidenza (con più inserzioni attive)
                    $featuredCards = CardModel::with([
                        'category',
                        'cardSet',
                        'player',
                        'team',
                        'league'
                    ])
                    ->withCount(['cardListings' => function ($query) {
                        $query->where('status', 'active');
                    }])
                    ->where('is_active', true)
                    ->orderBy('card_listings_count', 'desc')
                    ->limit(20)
                    ->get();

                    // Statistiche generali
                    $stats = [
                        'total_cards' => CardModel::where('is_active', true)->count(),
                        'total_listings' => CardListing::where('status', 'active')->count(),
                        'total_categories' => $categories->count(),
                        'total_sets' => CardSet::count(),
                        'total_leagues' => League::count(),
                        'total_teams' => Team::count(),
                        'total_players' => DB::table('players')->count(),
                    ];

                    // Macro-aree per la homepage
                    $macroAreas = [
                        'hero_section' => [
                            'title' => 'Scopri il mondo delle carte da collezione',
                            'subtitle' => 'La piattaforma definitiva per collezionisti e venditori',
                            'cta_text' => 'Inizia a esplorare',
                            'background_image' => '/images/hero-bg.jpg',
                        ],
                        'featured_categories' => [
                            'title' => 'Categorie in evidenza',
                            'categories' => $categories->take(6),
                        ],
                        'popular_sets' => [
                            'title' => 'Set popolari',
                            'sets' => $popularSets->take(6),
                        ],
                        'featured_cards' => [
                            'title' => 'Carte in evidenza',
                            'cards' => $featuredCards->take(12),
                        ],
                        'leagues_section' => [
                            'title' => 'Leghe e competizioni',
                            'leagues' => $popularLeagues,
                        ],
                        'teams_section' => [
                            'title' => 'Squadre più popolari',
                            'teams' => $popularTeams,
                        ],
                    ];

                    return [
                        'macro_areas' => $macroAreas,
                        'statistics' => $stats,
                        'meta' => [
                            'last_updated' => now()->toISOString(),
                            'cache_duration' => 1800, // 30 minuti
                        ]
                    ];
                },
                ['homepage', 'categories', 'cards', 'sets', 'teams', 'leagues']
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero dei dati della homepage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get navigation menu data
     */
    public function navigation(): JsonResponse
    {
        try {
            // Menu principale
            $mainMenu = [
                [
                    'id' => 'home',
                    'label' => 'Home',
                    'url' => '/',
                    'icon' => 'home',
                ],
                [
                    'id' => 'catalog',
                    'label' => 'Catalogo',
                    'url' => '/catalog',
                    'icon' => 'search',
                    'children' => [
                        [
                            'id' => 'categories',
                            'label' => 'Categorie',
                            'url' => '/catalog/categories',
                        ],
                        [
                            'id' => 'sets',
                            'label' => 'Set',
                            'url' => '/catalog/sets',
                        ],
                        [
                            'id' => 'players',
                            'label' => 'Giocatori',
                            'url' => '/catalog/players',
                        ],
                        [
                            'id' => 'teams',
                            'label' => 'Squadre',
                            'url' => '/catalog/teams',
                        ],
                    ]
                ],
                [
                    'id' => 'sell',
                    'label' => 'Vendi',
                    'url' => '/sell',
                    'icon' => 'tag',
                    'requires_auth' => true,
                    'requires_seller' => true,
                ],
                [
                    'id' => 'account',
                    'label' => 'Account',
                    'url' => '/account',
                    'icon' => 'user',
                    'requires_auth' => true,
                    'children' => [
                        [
                            'id' => 'profile',
                            'label' => 'Profilo',
                            'url' => '/account/profile',
                        ],
                        [
                            'id' => 'listings',
                            'label' => 'Le mie inserzioni',
                            'url' => '/account/listings',
                            'requires_seller' => true,
                        ],
                        [
                            'id' => 'orders',
                            'label' => 'I miei ordini',
                            'url' => '/account/orders',
                        ],
                        [
                            'id' => 'wishlist',
                            'label' => 'Lista desideri',
                            'url' => '/account/wishlist',
                        ],
                    ]
                ],
            ];

            // Categorie per menu rapido
            $quickCategories = Category::select('id', 'name', 'slug', 'icon')
                ->withCount(['cardModels' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->orderBy('card_models_count', 'desc')
                ->limit(8)
                ->get();

            // Set recenti per menu rapido
            $recentSets = CardSet::select('id', 'name', 'year', 'brand')
                ->where('year', '>=', date('Y') - 2)
                ->orderBy('year', 'desc')
                ->orderBy('name')
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'main_menu' => $mainMenu,
                    'quick_categories' => $quickCategories,
                    'recent_sets' => $recentSets,
                    'meta' => [
                        'last_updated' => now()->toISOString(),
                        'cache_duration' => 1800, // 30 minuti
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero del menu di navigazione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get search suggestions
     */
    public function searchSuggestions(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        try {
            $suggestions = [];

            // Suggerimenti da modelli di carte
            $cardSuggestions = CardModel::select('id', 'name', 'set_name', 'year')
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('set_name', 'LIKE', "%{$query}%");
                })
                ->limit(5)
                ->get();

            foreach ($cardSuggestions as $card) {
                $suggestions[] = [
                    'type' => 'card',
                    'id' => $card->id,
                    'text' => $card->name,
                    'subtext' => $card->set_name . ' (' . $card->year . ')',
                    'url' => "/cards/{$card->id}",
                ];
            }

            // Suggerimenti da giocatori
            $playerSuggestions = DB::table('players')
                ->select('id', 'name', 'team_id')
                ->where('name', 'LIKE', "%{$query}%")
                ->limit(3)
                ->get();

            foreach ($playerSuggestions as $player) {
                $suggestions[] = [
                    'type' => 'player',
                    'id' => $player->id,
                    'text' => $player->name,
                    'subtext' => 'Giocatore',
                    'url' => "/players/{$player->id}",
                ];
            }

            // Suggerimenti da squadre
            $teamSuggestions = Team::select('id', 'name', 'league_id')
                ->where('name', 'LIKE', "%{$query}%")
                ->limit(3)
                ->get();

            foreach ($teamSuggestions as $team) {
                $suggestions[] = [
                    'type' => 'team',
                    'id' => $team->id,
                    'text' => $team->name,
                    'subtext' => 'Squadra',
                    'url' => "/teams/{$team->id}",
                ];
            }

            // Suggerimenti da set
            $setSuggestions = CardSet::select('id', 'name', 'year', 'brand')
                ->where('name', 'LIKE', "%{$query}%")
                ->limit(3)
                ->get();

            foreach ($setSuggestions as $set) {
                $suggestions[] = [
                    'type' => 'set',
                    'id' => $set->id,
                    'text' => $set->name,
                    'subtext' => $set->brand . ' (' . $set->year . ')',
                    'url' => "/sets/{$set->id}",
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero dei suggerimenti',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trending content
     */
    public function trending(): JsonResponse
    {
        try {
            // Carte più cercate (simulato per ora)
            $trendingCards = CardModel::with([
                'category',
                'cardSet',
                'player',
                'team'
            ])
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(8)
            ->get();

            // Categorie più popolari
            $trendingCategories = Category::withCount(['cardModels' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('card_models_count', 'desc')
            ->limit(6)
            ->get();

            // Set più popolari
            $trendingSets = CardSet::where('year', '>=', date('Y') - 3)
                ->withCount(['cardModels' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->orderBy('card_models_count', 'desc')
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'trending_cards' => $trendingCards,
                    'trending_categories' => $trendingCategories,
                    'trending_sets' => $trendingSets,
                    'meta' => [
                        'last_updated' => now()->toISOString(),
                        'cache_duration' => 7200, // 2 ore
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero dei contenuti in tendenza',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
