<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardModel;
use App\Models\CardListing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CardController extends Controller
{
    /**
     * Get cards by category and section
     */
    public function getCardsByCategory(Request $request): JsonResponse
    {
        $category = $request->get('category'); // football, basketball, pokemon
        $section = $request->get('section'); // top_players, top_trend, new, most_expensive
        $limit = $request->get('limit', 8);

        try {
            // For "new" and "most_expensive" sections, use CardListing instead of CardModel
            if ($section === 'new' || $section === 'most_expensive') {
                return $this->getCardsFromListings($category, $section, $limit);
            }

            // Base query for card models with category relationship
            $query = CardModel::with('category');

            // Filter by category using the relationship
            if ($category) {
                switch ($category) {
                    case 'football':
                        $query->whereHas('category', function($q) {
                            $q->where('name', 'Calcio')
                              ->orWhere('slug', 'calcio')
                              ->orWhere('slug', 'football');
                        });
                        break;
                    case 'basketball':
                        $query->whereHas('category', function($q) {
                            $q->where('name', 'Basketball')
                              ->orWhere('slug', 'basketball')
                              ->orWhere('slug', 'basket');
                        });
                        break;
                    case 'pokemon':
                        $query->whereHas('category', function($q) {
                            $q->where('name', 'Pokemon')
                              ->orWhere('slug', 'pokemon')
                              ->orWhere('slug', 'tcg');
                        });
                        break;
                    case 'disney':
                        $query->whereHas('category', function($q) {
                            $q->where('name', 'Disney')
                              ->orWhere('slug', 'disney');
                        });
                        break;
                    case 'spongebob':
                        $query->whereHas('category', function($q) {
                            $q->where('name', 'Spongebob')
                              ->orWhere('slug', 'spongebob');
                        });
                        break;
                    default:
                        // Per altre categorie, usa lo slug direttamente
                        $query->whereHas('category', function($q) use ($category) {
                            $q->where('slug', $category);
                        });
                        break;
                }
            }

            // Apply section-specific logic
            switch ($section) {
                case 'top_players':
                    // Get most popular cards (by creation date for now)
                    $query->orderBy('created_at', 'desc');
                    break;
                
                case 'top_trend':
                    // Get recently added cards
                    $query->where('created_at', '>=', now()->subDays(30))
                          ->orderBy('created_at', 'desc');
                    break;
                
                default:
                    $query->orderBy('created_at', 'desc');
            }

            $cards = $query->limit($limit)->get();

            // Transform data for frontend
            $transformedCards = $cards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'name' => $card->name ?? 'Nome non disponibile',
                    'team' => $this->getTeamName($card),
                    'type' => $this->getCategoryType($card->category->name ?? ''),
                    'description' => $card->description ?? 'Descrizione non disponibile',
                    'price' => $this->getEstimatedPrice($card),
                    'rating' => $this->getEstimatedRating($card),
                    'image_url' => $card->image_url,
                    'created_at' => $card->created_at,
                    'rarity' => $card->rarity,
                    'set_name' => $card->set_name,
                ];
            });

            // Log missing data for internal tracking
            $missingData = $this->getMissingDataInfo($cards);
            if ($missingData['missing_images'] > 0 || $missingData['missing_prices'] > 0 || $missingData['missing_ratings'] > 0) {
                Log::info('Missing data detected', [
                    'category' => $category,
                    'section' => $section,
                    'missing_data' => $missingData
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $transformedCards,
                'category' => $category,
                'section' => $section,
                'count' => $transformedCards->count()
            ]);

        } catch (\Exception $e) {
            // Log error for internal tracking
            Log::error('Error fetching cards', [
                'category' => $category,
                'section' => $section,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore nel recupero delle carte: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get cards from active listings for "new" and "most_expensive" sections
     */
    private function getCardsFromListings(?string $category, string $section, int $limit): JsonResponse
    {
        try {
            // Base query for active listings with card model and category relationships
            $query = CardListing::with([
                'cardModel.category',
                'cardModel.player',
                'cardModel.team',
                'cardModel.cardSet'
            ])->where('status', 'active');

            // Filter by category using the relationship
            if ($category) {
                switch ($category) {
                    case 'football':
                        $query->whereHas('cardModel.category', function($q) {
                            $q->where('name', 'Calcio')
                              ->orWhere('slug', 'calcio')
                              ->orWhere('slug', 'football');
                        });
                        break;
                    case 'basketball':
                        $query->whereHas('cardModel.category', function($q) {
                            $q->where('name', 'Basketball')
                              ->orWhere('slug', 'basketball')
                              ->orWhere('slug', 'basket');
                        });
                        break;
                    case 'pokemon':
                        $query->whereHas('cardModel.category', function($q) {
                            $q->where('name', 'Pokemon')
                              ->orWhere('slug', 'pokemon')
                              ->orWhere('slug', 'tcg');
                        });
                        break;
                    case 'disney':
                        $query->whereHas('cardModel.category', function($q) {
                            $q->where('name', 'Disney')
                              ->orWhere('slug', 'disney');
                        });
                        break;
                    case 'spongebob':
                        $query->whereHas('cardModel.category', function($q) {
                            $q->where('name', 'Spongebob')
                              ->orWhere('slug', 'spongebob');
                        });
                        break;
                    default:
                        // Per altre categorie, usa lo slug direttamente
                        $query->whereHas('cardModel.category', function($q) use ($category) {
                            $q->where('slug', $category);
                        });
                        break;
                }
            }

            // Apply section-specific ordering
            switch ($section) {
                case 'new':
                    // Get newest listings (most recently created)
                    $query->orderBy('created_at', 'desc');
                    break;
                
                case 'most_expensive':
                    // Get most expensive listings (highest price first)
                    $query->orderBy('price', 'desc');
                    break;
                
                default:
                    $query->orderBy('created_at', 'desc');
            }

            $listings = $query->limit($limit)->get();

            // Transform data for frontend
            $transformedCards = $listings->map(function ($listing) {
                $cardModel = $listing->cardModel;
                
                // Skip if cardModel is missing (shouldn't happen, but safety check)
                if (!$cardModel) {
                    return null;
                }
                
                // Get image from listing (priority) or fallback to card model
                $imageUrl = null;
                if ($listing->images && is_array($listing->images) && count($listing->images) > 0) {
                    $firstImage = $listing->images[0];
                    if (!str_starts_with($firstImage, '/storage/') && !str_starts_with($firstImage, 'http')) {
                        $imageUrl = '/storage/' . $firstImage;
                    } else {
                        $imageUrl = $firstImage;
                    }
                } elseif ($cardModel->image_url) {
                    $imageUrl = $cardModel->image_url;
                }

                // Get player name
                $playerName = $cardModel->player->name ?? $cardModel->player_name ?? $cardModel->name ?? 'Nome non disponibile';
                
                // Get team name
                $teamName = 'Team non disponibile';
                if ($cardModel->team) {
                    $teamName = $cardModel->team->name;
                } elseif ($cardModel->set_name) {
                    $teamName = $cardModel->set_name;
                }

                return [
                    'id' => $cardModel->id,
                    'listing_id' => $listing->id,
                    'name' => $playerName,
                    'team' => $teamName,
                    'type' => $this->getCategoryType($cardModel->category->name ?? ''),
                    'description' => $listing->description ?? $cardModel->description ?? 'Descrizione non disponibile',
                    'price' => '€' . number_format($listing->price, 2, ',', '.'), // Formato italiano
                    'rating' => $this->getEstimatedRating($cardModel),
                    'image_url' => $imageUrl,
                    'images' => $listing->images ?? [],
                    'created_at' => $listing->created_at,
                    'rarity' => $cardModel->rarity ?? null,
                    'set_name' => $cardModel->cardSet->name ?? $cardModel->set_name ?? null,
                ];
            })->filter(); // Remove null entries

            return response()->json([
                'success' => true,
                'data' => $transformedCards,
                'category' => $category,
                'section' => $section,
                'count' => $transformedCards->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching cards from listings', [
                'category' => $category,
                'section' => $section,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore nel recupero delle carte: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get missing data information
     */
    private function getMissingDataInfo($cards): array
    {
        $missingData = [
            'total_cards' => $cards->count(),
            'missing_images' => 0,
            'missing_prices' => 0,
            'missing_ratings' => 0,
            'missing_descriptions' => 0,
            'categories_needing_data' => [],
            'suggestions' => []
        ];

        foreach ($cards as $card) {
            if (empty($card->image_url)) {
                $missingData['missing_images']++;
            }
            if (empty($card->price)) {
                $missingData['missing_prices']++;
            }
            if (empty($card->rating)) {
                $missingData['missing_ratings']++;
            }
            if (empty($card->description)) {
                $missingData['missing_descriptions']++;
            }
        }

        // Add suggestions based on missing data
        if ($missingData['missing_images'] > 0) {
            $missingData['suggestions'][] = 'Aggiungere immagini alle carte per migliorare l\'esperienza utente';
        }
        if ($missingData['missing_prices'] > 0) {
            $missingData['suggestions'][] = 'Completare i prezzi delle carte per la funzionalità di vendita';
        }
        if ($missingData['missing_ratings'] > 0) {
            $missingData['suggestions'][] = 'Aggiungere rating alle carte per il sistema di raccomandazioni';
        }

        return $missingData;
    }

    /**
     * Get category type for frontend
     */
    private function getCategoryType($categoryName): string
    {
        switch (strtolower($categoryName)) {
            case 'calcio':
            case 'football':
            case 'soccer':
                return 'Calcio';
            case 'basketball':
            case 'basket':
                return 'Basketball';
            case 'pokemon':
            case 'tcg':
                return 'Pokemon';
            case 'disney':
                return 'Disney';
            case 'spongebob':
                return 'Spongebob';
            default:
                return 'Carta da collezione';
        }
    }

    /**
     * Get team name from card data
     */
    private function getTeamName($card): string
    {
        // Try to extract team from name or use set name as fallback
        if ($card->team_id) {
            // If we had a team relationship, we could use it here
            return 'Team non disponibile';
        }
        
        // Use set name as team indicator
        return $card->set_name ?? 'Team non disponibile';
    }

    /**
     * Get estimated price based on rarity
     */
    private function getEstimatedPrice($card): string
    {
        $basePrice = 10; // Base price in euros
        
        switch ($card->rarity) {
            case 'mythic':
                $multiplier = 50;
                break;
            case 'rare':
                $multiplier = 25;
                break;
            case 'uncommon':
                $multiplier = 5;
                break;
            case 'common':
                $multiplier = 1;
                break;
            default:
                $multiplier = 2;
        }
        
        $price = $basePrice * $multiplier;
        return '€' . number_format($price, 2, ',', '.'); // Formato italiano: punto per migliaia, virgola per decimali
    }

    /**
     * Get estimated rating based on rarity and other factors
     */
    private function getEstimatedRating($card): string
    {
        if (!$card) {
            return number_format(4.0, 1);
        }
        
        $baseRating = 4.0;
        
        switch ($card->rarity ?? null) {
            case 'mythic':
                $rating = 4.8;
                break;
            case 'rare':
                $rating = 4.5;
                break;
            case 'uncommon':
                $rating = 4.2;
                break;
            case 'common':
                $rating = 3.8;
                break;
            default:
                $rating = $baseRating;
        }
        
        // Add bonus for special cards
        if ($card->is_rookie ?? false) {
            $rating += 0.2;
        }
        if ($card->is_star ?? false) {
            $rating += 0.3;
        }
        if ($card->is_legend ?? false) {
            $rating += 0.4;
        }
        if ($card->is_autograph ?? false) {
            $rating += 0.5;
        }
        if ($card->is_relic ?? false) {
            $rating += 0.3;
        }
        
        return number_format(min($rating, 5.0), 1);
    }

    /**
     * Get single card details by ID
     * Restituisce i dati dalla CardListing più recente per questa carta
     */
    public function getCardDetails($id): JsonResponse
    {
        try {
            $card = CardModel::with(['category', 'player', 'team', 'cardSet'])
                ->where('id', $id)
                ->first();

            if (!$card) {
                return response()->json([
                    'success' => false,
                    'error' => 'Carta non trovata'
                ], 404);
            }

            // Cerca la CardListing più recente attiva per questa carta
            $listing = CardListing::where('card_model_id', $id)
                ->where('status', 'active')
                ->with(['seller'])
                ->orderBy('created_at', 'desc')
                ->first();

            // Se c'è una CardListing, usa i suoi dati reali
            if ($listing) {
                // Priorità alle immagini reali dalle CardListing
                $imageUrl = null;
                $images = [];
                if ($listing->images && is_array($listing->images) && count($listing->images) > 0) {
                    $images = $listing->images;
                    $firstImage = $listing->images[0];
                    if (!str_starts_with($firstImage, '/storage/') && !str_starts_with($firstImage, 'http')) {
                        $imageUrl = '/storage/' . $firstImage;
                    } else {
                        $imageUrl = $firstImage;
                    }
                } elseif ($card->image_url) {
                    $imageUrl = $card->image_url;
                }

                $transformedCard = [
                    'id' => $card->id,
                    'listing_id' => $listing->id,
                    'name' => $card->player->name ?? $card->player_name ?? $card->name ?? 'Player',
                    'team' => $card->team->name ?? $this->getTeamName($card),
                    'set_name' => $card->cardSet->name ?? $card->set_name ?? 'Set Name',
                    'year' => $card->year ?: date('Y'),
                    'rarity' => $card->rarity ?: 'Rare',
                    'price' => number_format($listing->price, 2, ',', '.'), // Formato italiano: punto per migliaia, virgola per decimali
                    'rating' => $this->getEstimatedRating($card),
                    'image_url' => $imageUrl,
                    'images' => $images,
                    'category' => $this->getCategoryType($card->category->name ?? ''),
                    'description' => $listing->description ?? $card->description,
                    'condition' => $listing->condition ?? 'LIGHT PLAYED',
                    'card_number_in_set' => $card->card_number_in_set,
                    'is_autograph' => $card->is_autograph ?? false,
                    'is_relic' => $card->is_relic ?? false,
                    'is_rookie' => $card->is_rookie ?? false,
                    'is_star' => $card->is_star ?? false,
                    'is_legend' => $card->is_legend ?? false,
                    'card_number' => $card->card_number,
                    'quantity' => $listing->quantity ?? 1,
                    'seller' => [
                        'id' => $listing->seller->id ?? null,
                        'name' => $listing->seller->name ?? 'Venditore',
                        'email' => $listing->seller->email ?? null,
                        'total_sales' => $listing->seller->total_sales ?? 0,
                        'rating' => $listing->seller->rating ?? 0
                    ],
                    'created_at' => $card->created_at,
                    'updated_at' => $card->updated_at
                ];
            } else {
                // Fallback ai dati del CardModel se non c'è una CardListing
                $transformedCard = [
                    'id' => $card->id,
                    'name' => $card->player->name ?? $card->player_name ?? $card->name ?? 'Player',
                    'team' => $card->team->name ?? $this->getTeamName($card),
                    'set_name' => $card->cardSet->name ?? $card->set_name ?? 'Set Name',
                    'year' => $card->year ?: date('Y'),
                    'rarity' => $card->rarity ?: 'Rare',
                    'price' => $this->getEstimatedPrice($card),
                    'rating' => $this->getEstimatedRating($card),
                    'image_url' => $card->image_url,
                    'images' => [],
                    'category' => $this->getCategoryType($card->category->name ?? ''),
                    'description' => $card->description,
                    'condition' => $card->condition ?: 'LIGHT PLAYED',
                    'card_number_in_set' => $card->card_number_in_set,
                    'is_autograph' => $card->is_autograph ?? false,
                    'is_relic' => $card->is_relic ?? false,
                    'is_rookie' => $card->is_rookie ?? false,
                    'is_star' => $card->is_star ?? false,
                    'is_legend' => $card->is_legend ?? false,
                    'card_number' => $card->card_number,
                    'created_at' => $card->created_at,
                    'updated_at' => $card->updated_at
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $transformedCard
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching card details', [
                'card_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore nel recupero dei dettagli della carta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get card details by category and slug
     */
    public function getCardDetailsBySlug($category, $slug): JsonResponse
    {
        try {
            // Mappa le categorie URL alle categorie del database
            $categoryMap = [
                'football' => 'calcio',
                'basketball' => 'basketball', 
                'pokemon' => 'pokemon',
                'disney' => 'disney',
                'spongebob' => 'spongebob'
            ];

            $dbCategory = $categoryMap[$category] ?? $category;

            // Se lo slug contiene "/", estrai solo l'ultima parte (es. "56/elsa" -> "elsa")
            $cleanSlug = $slug;
            if (strpos($slug, '/') !== false) {
                $parts = explode('/', $slug);
                $cleanSlug = end($parts); // Prendi l'ultima parte
            }

            // Prima prova a cercare per slug esatto (con slug pulito)
            $card = CardModel::with(['category', 'player', 'team', 'cardSet'])
                ->whereHas('category', function($q) use ($dbCategory) {
                    $q->where('slug', $dbCategory);
                })
                ->where('slug', $cleanSlug)
                ->first();
            
            // Se non trovato per slug, prova con lo slug originale (potrebbe essere un formato diverso)
            if (!$card && $cleanSlug !== $slug) {
                $card = CardModel::with(['category', 'player', 'team', 'cardSet'])
                    ->whereHas('category', function($q) use ($dbCategory) {
                        $q->where('slug', $dbCategory);
                    })
                    ->where('slug', $slug)
                    ->first();
            }
            
            // Se non trovato per slug, prova con il nome (usa lo slug pulito)
            if (!$card) {
                $cardName = str_replace('-', ' ', $cleanSlug);
                $cardName = ucwords($cardName); // Converte in formato nome (es. "lionel messi")
                
                $card = CardModel::with(['category', 'player', 'team', 'cardSet'])
                    ->whereHas('category', function($q) use ($dbCategory) {
                        $q->where('slug', $dbCategory);
                    })
                    ->where('name', 'LIKE', '%' . $cardName . '%')
                    ->first();
            }

            if (!$card) {
                return response()->json([
                    'success' => false,
                    'error' => 'Carta non trovata'
                ], 404);
            }

            // Cerca la CardListing più recente attiva per questa carta
            $listing = CardListing::where('card_model_id', $card->id)
                ->where('status', 'active')
                ->with(['seller'])
                ->orderBy('created_at', 'desc')
                ->first();

            // Se c'è una CardListing, usa i suoi dati reali
            if ($listing) {
                // Priorità alle immagini reali dalle CardListing
                $imageUrl = null;
                $images = [];
                if ($listing->images && is_array($listing->images) && count($listing->images) > 0) {
                    $images = $listing->images;
                    $firstImage = $listing->images[0];
                    if (!str_starts_with($firstImage, '/storage/') && !str_starts_with($firstImage, 'http')) {
                        $imageUrl = '/storage/' . $firstImage;
                    } else {
                        $imageUrl = $firstImage;
                    }
                } elseif ($card->image_url) {
                    $imageUrl = $card->image_url;
                }

                $transformedCard = [
                    'id' => $card->id,
                    'listing_id' => $listing->id,
                    'name' => $card->player->name ?? $card->player_name ?? $card->name ?? 'Player',
                    'team' => $card->team->name ?? $this->getTeamName($card),
                    'set_name' => $card->cardSet->name ?? $card->set_name ?? 'Set Name',
                    'year' => $card->year ?: date('Y'),
                    'rarity' => $card->rarity ?: 'Rare',
                    'price' => number_format($listing->price, 2, ',', '.'), // Formato italiano: punto per migliaia, virgola per decimali
                    'rating' => $this->getEstimatedRating($card),
                    'image_url' => $imageUrl,
                    'images' => $images,
                    'category' => $this->getCategoryType($card->category->name ?? ''),
                    'description' => $listing->description ?? $card->description,
                    'condition' => $listing->condition ?? 'LIGHT PLAYED',
                    'card_number_in_set' => $card->card_number_in_set,
                    'is_autograph' => $card->is_autograph ?? false,
                    'is_relic' => $card->is_relic ?? false,
                    'is_rookie' => $card->is_rookie ?? false,
                    'is_star' => $card->is_star ?? false,
                    'is_legend' => $card->is_legend ?? false,
                    'card_number' => $card->card_number,
                    'quantity' => $listing->quantity ?? 1,
                    'seller' => [
                        'id' => $listing->seller->id ?? null,
                        'name' => $listing->seller->name ?? 'Venditore',
                        'email' => $listing->seller->email ?? null,
                        'total_sales' => $listing->seller->total_sales ?? 0,
                        'rating' => $listing->seller->rating ?? 0
                    ],
                    'created_at' => $card->created_at,
                    'updated_at' => $card->updated_at
                ];
            } else {
                // Fallback ai dati del CardModel se non c'è una CardListing
                $transformedCard = [
                    'id' => $card->id,
                    'name' => $card->player->name ?? $card->player_name ?? $card->name ?? 'Player',
                    'team' => $card->team->name ?? $this->getTeamName($card),
                    'set_name' => $card->cardSet->name ?? $card->set_name ?? 'Set Name',
                    'year' => $card->year ?: date('Y'),
                    'rarity' => $card->rarity ?: 'Rare',
                    'price' => $this->getEstimatedPrice($card),
                    'rating' => $this->getEstimatedRating($card),
                    'image_url' => $card->image_url,
                    'images' => [],
                    'category' => $this->getCategoryType($card->category->name ?? ''),
                    'description' => $card->description,
                    'condition' => $card->condition ?: 'LIGHT PLAYED',
                    'card_number_in_set' => $card->card_number_in_set,
                    'is_autograph' => $card->is_autograph ?? false,
                    'is_relic' => $card->is_relic ?? false,
                    'is_rookie' => $card->is_rookie ?? false,
                    'is_star' => $card->is_star ?? false,
                    'is_legend' => $card->is_legend ?? false,
                    'card_number' => $card->card_number,
                    'created_at' => $card->created_at,
                    'updated_at' => $card->updated_at
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $transformedCard
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching card details by slug', [
                'category' => $category,
                'slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore nel recupero dei dettagli della carta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get related products for a specific card
     */
    public function getRelatedProducts($cardId, Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 8);
            
            // Prima recupera la carta principale
            $mainCard = CardModel::with(['category', 'player', 'team', 'cardSet'])
                ->where('id', $cardId)
                ->first();

            if (!$mainCard) {
                return response()->json([
                    'success' => false,
                    'error' => 'Carta principale non trovata'
                ], 404);
            }

            // Query per carte correlate usando criteri multipli
            // Mostra solo carte che hanno almeno una listing attiva
            $relatedQuery = CardModel::with(['category', 'player', 'team', 'cardSet'])
                ->where('id', '!=', $cardId) // Esclude la carta principale
                ->where('is_active', true)
                ->whereHas('cardListings', function($q) {
                    $q->where('status', 'active');
                });

            // Applica filtri basati sui criteri di similarità
            $this->applyRelatedFilters($relatedQuery, $mainCard);

            // Determina se è una categoria senza player (Disney, Spongebob)
            $isNonPlayerCategory = in_array($mainCard->category->slug ?? '', ['disney', 'spongebob']);
            
            // Ordina per rilevanza (priorità: stesso set, stessa squadra, stesso anno, stessa rarità)
            if ($isNonPlayerCategory) {
                // Per Disney/Spongebob, ordina senza usare player_id
                $relatedQuery->orderByRaw("
                    CASE 
                        WHEN card_set_id = ? THEN 1
                        WHEN year = ? AND category_id = ? THEN 2
                        WHEN rarity = ? AND category_id = ? THEN 3
                        WHEN category_id = ? THEN 4
                        ELSE 5
                    END,
                    created_at DESC
                ", [
                    $mainCard->card_set_id,
                    $mainCard->year, $mainCard->category_id,
                    $mainCard->rarity, $mainCard->category_id,
                    $mainCard->category_id
                ]);
            } else {
                // Per categorie con player, usa la logica originale
                $relatedQuery->orderByRaw("
                    CASE 
                        WHEN card_set_id = ? AND (player_id != ? OR player_id IS NULL) THEN 1
                        WHEN team_id = ? AND (player_id != ? OR player_id IS NULL) THEN 2  
                        WHEN year = ? AND category_id = ? AND (player_id != ? OR player_id IS NULL) THEN 3
                        WHEN rarity = ? AND category_id = ? AND (player_id != ? OR player_id IS NULL) THEN 4
                        WHEN category_id = ? AND (player_id != ? OR player_id IS NULL) THEN 5
                        ELSE 6
                    END,
                    created_at DESC
                ", [
                    $mainCard->card_set_id, $mainCard->player_id,
                    $mainCard->team_id, $mainCard->player_id,
                    $mainCard->year, $mainCard->category_id, $mainCard->player_id,
                    $mainCard->rarity, $mainCard->category_id, $mainCard->player_id,
                    $mainCard->category_id, $mainCard->player_id
                ]);
            }

            $relatedCards = $relatedQuery->limit($limit)->get();
            
            // Determina se è una categoria senza player (Disney, Spongebob)
            $isNonPlayerCategory = in_array($mainCard->category->slug ?? '', ['disney', 'spongebob']);
            $mainCardBaseName = null;
            if ($isNonPlayerCategory && $mainCard->name) {
                $parts = explode(' - ', $mainCard->name);
                $mainCardBaseName = $parts[0] ?? null;
            }
            
            // Debug info per la risposta
            $debugInfo = [
                'main_card_id' => $mainCard->id,
                'main_card_name' => $mainCard->name,
                'main_card_category' => $mainCard->category->slug ?? null,
                'main_card_set_id' => $mainCard->card_set_id,
                'main_card_year' => $mainCard->year,
                'main_card_rarity' => $mainCard->rarity,
                'is_non_player_category' => $isNonPlayerCategory,
                'main_card_base_name' => $mainCardBaseName ?? null,
                'related_count_after_filters' => $relatedCards->count(),
                'limit' => $limit,
            ];

            // Se non abbiamo abbastanza risultati con i criteri principali, 
            // espandiamo la ricerca nella stessa categoria
            if ($relatedCards->count() < $limit) {
                $remainingLimit = $limit - $relatedCards->count();
                
                $fallbackQuery = CardModel::with(['category', 'player', 'team', 'cardSet'])
                    ->where('id', '!=', $cardId)
                    ->where('is_active', true)
                    ->where('category_id', $mainCard->category_id)
                    ->whereHas('cardListings', function($q) {
                        $q->where('status', 'active');
                    })
                    ->whereNotIn('id', $relatedCards->pluck('id'));
                
                // Se è Disney/Spongebob, escludi carte con lo stesso nome base
                if ($isNonPlayerCategory && $mainCardBaseName) {
                    $fallbackQuery->whereRaw('SUBSTRING_INDEX(name, \' - \', 1) != ?', [$mainCardBaseName]);
                }
                
                $fallbackCards = $fallbackQuery->orderBy('created_at', 'desc')
                    ->limit($remainingLimit)
                    ->get();

                $debugInfo['fallback_count'] = $fallbackCards->count();
                $relatedCards = $relatedCards->merge($fallbackCards);
            }
            
            $debugInfo['total_related_count'] = $relatedCards->count();
            
            // Conta quante carte Disney/Spongebob ci sono nel database (per debug)
            if ($isNonPlayerCategory) {
                $totalCardsInCategory = CardModel::where('category_id', $mainCard->category_id)
                    ->where('is_active', true)
                    ->count();
                $debugInfo['total_cards_in_category'] = $totalCardsInCategory;
            }

            // Trasforma i dati per il frontend
            $transformedCards = $relatedCards->map(function ($card) {
                // Per Disney/Spongebob, estrai solo il nome base (prima del " - ")
                $cardName = $card->player_name ?: $card->name ?: 'Player';
                if (in_array($card->category->slug ?? '', ['disney', 'spongebob']) && $cardName) {
                    $parts = explode(' - ', $cardName);
                    $cardName = $parts[0] ?? $cardName;
                }
                
                return [
                    'id' => $card->id,
                    'name' => $cardName,
                    'team' => $this->getTeamName($card),
                    'type' => $this->getCategoryType($card->category->name ?? ''),
                    'price' => $this->getEstimatedPrice($card),
                    'rating' => $this->getEstimatedRating($card),
                    'image_url' => $card->image_url,
                    'set_name' => $card->set_name,
                    'year' => $card->year,
                    'rarity' => $card->rarity,
                    'slug' => $card->slug,
                    'category_slug' => $this->getCategorySlug($card->category->slug ?? ''),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedCards,
                'count' => $transformedCards->count(),
                'criteria' => $this->getRelatedCriteria($mainCard),
                'debug' => $debugInfo ?? [] // Aggiunto per debug temporaneo
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching related products', [
                'card_id' => $cardId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore nel recupero dei prodotti correlati: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply related product filters based on main card criteria
     */
    private function applyRelatedFilters($query, $mainCard): void
    {
        // Determina se è una categoria senza player (Disney, Spongebob)
        $isNonPlayerCategory = in_array($mainCard->category->slug ?? '', ['disney', 'spongebob']);
        
        // Estrai il nome base per Disney/Spongebob (prima del " - ")
        $mainCardBaseName = null;
        if ($isNonPlayerCategory && $mainCard->name) {
            $parts = explode(' - ', $mainCard->name);
            $mainCardBaseName = $parts[0] ?? null;
        }
        
        // Usa where con una funzione che raggruppa tutti i criteri OR
        $query->where(function($q) use ($mainCard, $isNonPlayerCategory, $mainCardBaseName) {
            // 1. Stesso set (se disponibile) - carte diverse dello stesso set
            if ($mainCard->card_set_id) {
                if ($isNonPlayerCategory && $mainCardBaseName) {
                    // Per Disney/Spongebob, escludi carte con lo stesso nome base
                    $q->where(function($subQ) use ($mainCard, $mainCardBaseName) {
                        $subQ->where('card_set_id', $mainCard->card_set_id)
                             ->whereRaw('SUBSTRING_INDEX(name, \' - \', 1) != ?', [$mainCardBaseName]);
                    });
                } else {
                    // Per categorie con player, escludi stesso giocatore
                    $subQ = $q->where(function($subQ) use ($mainCard) {
                        $subQ->where('card_set_id', $mainCard->card_set_id);
                        if ($mainCard->player_id) {
                            $subQ->where('player_id', '!=', $mainCard->player_id);
                        } else {
                            // Se non c'è player_id, escludi solo se player_id è NULL
                            $subQ->whereNull('player_id');
                        }
                    });
                }
            }
            
            // 2. Stessa squadra (solo per categorie con player)
            if (!$isNonPlayerCategory && $mainCard->team_id) {
                $q->orWhere(function($subQ) use ($mainCard) {
                    $subQ->where('team_id', $mainCard->team_id);
                    if ($mainCard->player_id) {
                        $subQ->where('player_id', '!=', $mainCard->player_id);
                    }
                });
            }
            
            // 3. Stesso anno e stessa categoria
            if ($mainCard->year) {
                $q->orWhere(function($subQ) use ($mainCard, $isNonPlayerCategory, $mainCardBaseName) {
                    $subQ->where('year', $mainCard->year)
                         ->where('category_id', $mainCard->category_id);
                    
                    if ($isNonPlayerCategory && $mainCardBaseName) {
                        $subQ->whereRaw('SUBSTRING_INDEX(name, \' - \', 1) != ?', [$mainCardBaseName]);
                    } elseif ($mainCard->player_id) {
                        $subQ->where('player_id', '!=', $mainCard->player_id);
                    }
                });
            }
            
            // 4. Stessa rarità e stessa categoria
            if ($mainCard->rarity) {
                $q->orWhere(function($subQ) use ($mainCard, $isNonPlayerCategory, $mainCardBaseName) {
                    $subQ->where('rarity', $mainCard->rarity)
                         ->where('category_id', $mainCard->category_id);
                    
                    if ($isNonPlayerCategory && $mainCardBaseName) {
                        $subQ->whereRaw('SUBSTRING_INDEX(name, \' - \', 1) != ?', [$mainCardBaseName]);
                    } elseif ($mainCard->player_id) {
                        $subQ->where('player_id', '!=', $mainCard->player_id);
                    }
                });
            }
            
            // 5. Fallback: stessa categoria (sempre incluso)
            $q->orWhere(function($subQ) use ($mainCard, $isNonPlayerCategory, $mainCardBaseName) {
                $subQ->where('category_id', $mainCard->category_id);
                
                if ($isNonPlayerCategory && $mainCardBaseName) {
                    $subQ->whereRaw('SUBSTRING_INDEX(name, \' - \', 1) != ?', [$mainCardBaseName]);
                } elseif ($mainCard->player_id) {
                    $subQ->where('player_id', '!=', $mainCard->player_id);
                }
            });
        });
    }

    /**
     * Get category slug for URL generation
     */
    private function getCategorySlug($categorySlug): string
    {
        $categoryMap = [
            'calcio' => 'football',
            'basketball' => 'basketball',
            'pokemon' => 'pokemon',
            'disney' => 'disney',
            'spongebob' => 'spongebob'
        ];
        
        return $categoryMap[$categorySlug] ?? $categorySlug;
    }

    /**
     * Get related products for a card by category and slug
     */
    public function getRelatedProductsBySlug($category, $slug, Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 8);
            
            // Mappa le categorie URL alle categorie del database
            $categoryMap = [
                'football' => 'calcio',
                'basketball' => 'basketball', 
                'pokemon' => 'pokemon',
                'disney' => 'disney',
                'spongebob' => 'spongebob'
            ];

            $dbCategory = $categoryMap[$category] ?? $category;

            // Se lo slug contiene "/", estrai solo l'ultima parte (es. "56/elsa" -> "elsa")
            $cleanSlug = $slug;
            if (strpos($slug, '/') !== false) {
                $parts = explode('/', $slug);
                $cleanSlug = end($parts); // Prendi l'ultima parte
            }

            // Prima prova a cercare per slug esatto (con slug pulito)
            $mainCard = CardModel::with(['category', 'player', 'team', 'cardSet'])
                ->whereHas('category', function($q) use ($dbCategory) {
                    $q->where('slug', $dbCategory);
                })
                ->where('slug', $cleanSlug)
                ->first();
            
            // Se non trovato per slug, prova con lo slug originale (potrebbe essere un formato diverso)
            if (!$mainCard && $cleanSlug !== $slug) {
                $mainCard = CardModel::with(['category', 'player', 'team', 'cardSet'])
                    ->whereHas('category', function($q) use ($dbCategory) {
                        $q->where('slug', $dbCategory);
                    })
                    ->where('slug', $slug)
                    ->first();
            }
            
            // Se non trovato per slug, prova con il nome (usa lo slug pulito)
            if (!$mainCard) {
                $cardName = str_replace('-', ' ', $cleanSlug);
                $cardName = ucwords($cardName); // Converte in formato nome (es. "lionel messi")
                
                $mainCard = CardModel::with(['category', 'player', 'team', 'cardSet'])
                    ->whereHas('category', function($q) use ($dbCategory) {
                        $q->where('slug', $dbCategory);
                    })
                    ->where('name', 'LIKE', '%' . $cardName . '%')
                    ->first();
            }

            if (!$mainCard) {
                return response()->json([
                    'success' => false,
                    'error' => 'Carta principale non trovata'
                ], 404);
            }

            // Query per carte correlate usando criteri multipli
            $relatedQuery = CardModel::with(['category', 'player', 'team', 'cardSet'])
                ->where('id', '!=', $mainCard->id) // Esclude la carta principale
                ->where('is_active', true)
                ->whereHas('cardListings', function($q) {
                    $q->where('status', 'active');
                });

            // Applica filtri basati sui criteri di similarità
            $this->applyRelatedFilters($relatedQuery, $mainCard);

            // Determina se è una categoria senza player (Disney, Spongebob)
            $isNonPlayerCategory = in_array($mainCard->category->slug ?? '', ['disney', 'spongebob']);
            
            // Ordina per rilevanza (priorità: stesso set, stessa squadra, stesso anno, stessa rarità)
            if ($isNonPlayerCategory) {
                // Per Disney/Spongebob, ordina senza usare player_id
                $relatedQuery->orderByRaw("
                    CASE 
                        WHEN card_set_id = ? THEN 1
                        WHEN year = ? AND category_id = ? THEN 2
                        WHEN rarity = ? AND category_id = ? THEN 3
                        WHEN category_id = ? THEN 4
                        ELSE 5
                    END,
                    created_at DESC
                ", [
                    $mainCard->card_set_id,
                    $mainCard->year, $mainCard->category_id,
                    $mainCard->rarity, $mainCard->category_id,
                    $mainCard->category_id
                ]);
            } else {
                // Per categorie con player, usa la logica originale
                $relatedQuery->orderByRaw("
                    CASE 
                        WHEN card_set_id = ? AND (player_id != ? OR player_id IS NULL) THEN 1
                        WHEN team_id = ? AND (player_id != ? OR player_id IS NULL) THEN 2  
                        WHEN year = ? AND category_id = ? AND (player_id != ? OR player_id IS NULL) THEN 3
                        WHEN rarity = ? AND category_id = ? AND (player_id != ? OR player_id IS NULL) THEN 4
                        WHEN category_id = ? AND (player_id != ? OR player_id IS NULL) THEN 5
                        ELSE 6
                    END,
                    created_at DESC
                ", [
                    $mainCard->card_set_id, $mainCard->player_id,
                    $mainCard->team_id, $mainCard->player_id,
                    $mainCard->year, $mainCard->category_id, $mainCard->player_id,
                    $mainCard->rarity, $mainCard->category_id, $mainCard->player_id,
                    $mainCard->category_id, $mainCard->player_id
                ]);
            }

            $relatedCards = $relatedQuery->limit($limit)->get();
            
            // Debug info per la risposta
            $debugInfo = [
                'main_card_id' => $mainCard->id,
                'main_card_name' => $mainCard->name,
                'main_card_category' => $mainCard->category->slug ?? null,
                'main_card_set_id' => $mainCard->card_set_id,
                'main_card_year' => $mainCard->year,
                'main_card_rarity' => $mainCard->rarity,
                'is_non_player_category' => $isNonPlayerCategory,
                'main_card_base_name' => $mainCardBaseName ?? null,
                'related_count_after_filters' => $relatedCards->count(),
                'limit' => $limit,
            ];

            // Se non abbiamo abbastanza risultati con i criteri principali, 
            // espandiamo la ricerca nella stessa categoria
            if ($relatedCards->count() < $limit) {
                $remainingLimit = $limit - $relatedCards->count();
                
                // Per Disney/Spongebob, escludi anche carte con lo stesso nome base
                $fallbackQuery = CardModel::with(['category', 'player', 'team', 'cardSet'])
                    ->where('id', '!=', $mainCard->id)
                    ->where('is_active', true)
                    ->where('category_id', $mainCard->category_id)
                    ->whereHas('cardListings', function($q) {
                        $q->where('status', 'active');
                    })
                    ->whereNotIn('id', $relatedCards->pluck('id'));
                
                // Se è Disney/Spongebob, escludi carte con lo stesso nome base
                if ($isNonPlayerCategory && $mainCardBaseName) {
                    $fallbackQuery->whereRaw('SUBSTRING_INDEX(name, \' - \', 1) != ?', [$mainCardBaseName]);
                }
                
                $fallbackCards = $fallbackQuery->orderBy('created_at', 'desc')
                    ->limit($remainingLimit)
                    ->get();

                $debugInfo['fallback_count'] = $fallbackCards->count();
                $relatedCards = $relatedCards->merge($fallbackCards);
            }
            
            $debugInfo['total_related_count'] = $relatedCards->count();
            
            // Conta quante carte Disney/Spongebob ci sono nel database (per debug)
            if ($isNonPlayerCategory) {
                $totalCardsInCategory = CardModel::where('category_id', $mainCard->category_id)
                    ->where('is_active', true)
                    ->count();
                $debugInfo['total_cards_in_category'] = $totalCardsInCategory;
            }

            // Trasforma i dati per il frontend
            $transformedCards = $relatedCards->map(function ($card) {
                // Per Disney/Spongebob, estrai solo il nome base (prima del " - ")
                $cardName = $card->player_name ?: $card->name ?: 'Player';
                if (in_array($card->category->slug ?? '', ['disney', 'spongebob']) && $cardName) {
                    $parts = explode(' - ', $cardName);
                    $cardName = $parts[0] ?? $cardName;
                }
                
                return [
                    'id' => $card->id,
                    'name' => $cardName,
                    'team' => $this->getTeamName($card),
                    'type' => $this->getCategoryType($card->category->name ?? ''),
                    'price' => $this->getEstimatedPrice($card),
                    'rating' => $this->getEstimatedRating($card),
                    'image_url' => $card->image_url,
                    'set_name' => $card->set_name,
                    'year' => $card->year,
                    'rarity' => $card->rarity,
                    'slug' => $card->slug,
                    'category_slug' => $this->getCategorySlug($card->category->slug ?? ''),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedCards,
                'count' => $transformedCards->count(),
                'criteria' => $this->getRelatedCriteria($mainCard),
                'debug' => $debugInfo ?? [] // Aggiunto per debug temporaneo
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching related products by slug', [
                'category' => $category,
                'slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore nel recupero dei prodotti correlati: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get criteria used for related products matching
     */
    private function getRelatedCriteria($mainCard): array
    {
        return [
            'player_id' => $mainCard->player_id,
            'card_set_id' => $mainCard->card_set_id,
            'team_id' => $mainCard->team_id,
            'category_id' => $mainCard->category_id,
            'year' => $mainCard->year,
            'rarity' => $mainCard->rarity,
            'criteria_explanation' => [
                'Stesso set (altri giocatori)' => $mainCard->card_set_id ? 'Applicato' : 'Non disponibile',
                'Stessa squadra (altri giocatori)' => $mainCard->team_id ? 'Applicato' : 'Non disponibile',
                'Stesso anno e categoria (altri giocatori)' => $mainCard->year ? 'Applicato' : 'Non disponibile',
                'Stessa rarità e categoria (altri giocatori)' => $mainCard->rarity ? 'Applicato' : 'Non disponibile',
                'Stessa categoria (altri giocatori)' => 'Sempre applicato come fallback',
                'Esclusione stesso giocatore' => 'Sempre applicato'
            ]
        ];
    }

    /**
     * Get all available categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = \App\Models\Category::withCount('cardModels')
                ->where('is_active', true)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'type' => $category->slug,
                        'name' => $category->name,
                        'display_name' => $this->getCategoryType($category->name),
                        'count' => $category->card_models_count,
                        'description' => $category->description
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nel recupero delle categorie: ' . $e->getMessage()
            ], 500);
        }
    }
}
