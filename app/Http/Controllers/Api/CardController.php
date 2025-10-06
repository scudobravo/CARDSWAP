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
                
                case 'new':
                    // Get newest cards
                    $query->orderBy('created_at', 'desc');
                    break;
                
                case 'most_expensive':
                    // Get cards ordered by rarity (since price doesn't exist)
                    $query->orderByRaw("CASE rarity 
                        WHEN 'mythic' THEN 1 
                        WHEN 'rare' THEN 2 
                        WHEN 'uncommon' THEN 3 
                        WHEN 'common' THEN 4 
                        ELSE 5 END")
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
        return '€' . number_format($price, 2);
    }

    /**
     * Get estimated rating based on rarity and other factors
     */
    private function getEstimatedRating($card): string
    {
        $baseRating = 4.0;
        
        switch ($card->rarity) {
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
        if ($card->is_rookie) {
            $rating += 0.2;
        }
        if ($card->is_star) {
            $rating += 0.3;
        }
        if ($card->is_legend) {
            $rating += 0.4;
        }
        
        return number_format(min($rating, 5.0), 1);
    }

    /**
     * Get single card details by ID
     */
    public function getCardDetails($id): JsonResponse
    {
        try {
            $card = CardModel::with(['category'])
                ->where('id', $id)
                ->first();

            if (!$card) {
                return response()->json([
                    'success' => false,
                    'error' => 'Carta non trovata'
                ], 404);
            }

            // Transform card data
            $transformedCard = [
                'id' => $card->id,
                'name' => $card->player_name ?: $card->name ?: 'Player',
                'team' => $this->getTeamName($card),
                'set_name' => $card->set_name ?: 'Set Name',
                'year' => $card->year ?: date('Y'),
                'rarity' => $card->rarity ?: 'Rare',
                'price' => $this->getEstimatedPrice($card),
                'rating' => $this->getEstimatedRating($card),
                'image_url' => $card->image_url,
                'category' => $this->getCategoryType($card),
                'description' => $card->description,
                'condition' => $card->condition ?: 'LIGHT PLAYED',
                'is_numbered' => $card->is_numbered ?? true,  // Fake data for testing
                'is_autograph' => $card->is_autograph ?? true,  // Fake data for testing
                'is_relic' => $card->is_relic ?? true,  // Fake data for testing
                'is_rookie' => $card->is_rookie ?? true,  // Fake data for testing
                'is_star' => $card->is_star ?? false,
                'is_legend' => $card->is_legend ?? false,
                'card_number' => $card->card_number,
                'serial_number' => $card->serial_number ?: '10',  // Fake data for testing
                'created_at' => $card->created_at,
                'updated_at' => $card->updated_at
            ];

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
