<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardModel;
use App\Models\CardListing;
use App\Models\Player;
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
            // For top_players/top_characters sections, also load player relationship
            $query = CardModel::with('category')->where('is_active', true);
            if ($section === 'top_players' || $section === 'top_characters') {
                $query->with('player');
            }

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
                case 'top_characters':
                    // For top_players/top_characters, we'll filter after getting cards
                    // Don't filter here - we'll do it in the grouping logic
                    break;
                
                case 'top_trend':
                    // Get recently added cards
                    $query->where('created_at', '>=', now()->subDays(30))
                          ->orderBy('created_at', 'desc');
                    break;
                
                default:
                    $query->orderBy('created_at', 'desc');
            }

            // For top_players/top_characters sections, we MUST show all players in the specified order
            if ($section === 'top_players' || $section === 'top_characters') {
                $topNames = [
                    'football' => ['Yamal', 'Messi', 'Cristiano Ronaldo', 'Ronaldo', 'Diego Maradona', 'Rodrigo Mora', 'Estevao Willian', 'Franco Mastantuono', 'Desire Doue', 'Erling Haaland', 'Kylian Mbappe', 'Roberto Lewandowski'],
                    'basketball' => ['Cooper Flagg', 'Viktor Wembanyama', 'Michael Jordan', 'Anthony Edwards', 'LeBron James', 'Luka Doncic', 'Nikola Jokic', 'Stephen Curry', 'Zaccharie Risacher', 'Kobe Bryant'],
                    'disney' => ['Mickey Mouse', 'Elsa', 'Donald Duck', 'Genie', 'Stitch', 'Whitesnow', 'Ariel', 'Belle', 'Cinderella', 'Mulan']
                ];

                $names = $topNames[$category] ?? [];
                $transformedCards = collect();

                foreach ($names as $index => $name) {
                    $normalizedName = strtolower(trim($name));
                    $item = [
                        'id' => 'top-' . $category . '-' . $index . '-' . time(),
                        'name' => $name,
                        'player_name' => $name,
                        'team' => 'Top ' . ucfirst($category),
                        'type' => $this->getCategoryType($category),
                        'description' => "Collezione ufficiale di {$name}",
                        'price' => '---',
                        'rating' => '5.0',
                        'image_url' => null,
                        'icon_path' => $this->getPlayerIconPath($name, $category),
                        'listing_id' => null,
                        'category_slug' => $category,
                        'slug' => strtolower(str_replace(' ', '-', $name))
                    ];

                    try {
                        $player = Player::where('name', 'LIKE', "%{$name}%")->first();
                        if ($player) {
                            $item['slug'] = $player->slug;
                            $card = CardModel::where('player_id', $player->id)->where('is_active', true)->first();
                            if ($card) {
                                $item['description'] = $card->description ?? $item['description'];
                                $item['price'] = $this->getEstimatedPrice($card);
                                $item['image_url'] = $card->image_url;
                            }
                        }
                    } catch (\Exception $e) {}

                    $transformedCards->push($item);
                }

                return response()->json([
                    'success' => true,
                    'data' => $transformedCards,
                    'category' => $category,
                    'section' => $section,
                    'count' => $transformedCards->count(),
                    'timestamp' => now()->timestamp
                ]);
            }

            // Normal processing for other sections
            $cards = $query->limit($limit)->get();

            // Transform data for frontend
            $transformedCards = $cards->map(function ($card) use ($section, $category) {
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
     * Get top player names for a category (exact order for display)
     */
    private function getTopPlayerNames(?string $category): array
    {
        $topPlayers = [
            'football' => [
                'Yamal',
                'Messi',
                'Cristiano Ronaldo',
                'Ronaldo',
                'Diego Maradona',
                'Rodrigo Mora',
                'Estevao Willian',
                'Franco Mastantuono',
                'Desire Doue',
                'Erling Haaland',
                'Kylian Mbappe',
                'Roberto Lewandowski',
            ],
            'basketball' => [
                'Cooper Flagg',
                'Viktor Wembanyama',
                'Michael Jordan',
                'Anthony Edwards',
                'LeBron James',
                'Luka Doncic',
                'Nikola Jokic',
                'Stephen Curry',
                'Zaccharie Risacher',
                'Kobe Bryant',
            ],
            'disney' => [
                'Mickey Mouse',
                'Elsa',
                'Donald Duck',
                'Genie',
                'Stitch',
                'Whitesnow',
                'Ariel',
                'Belle',
                'Cinderella',
                'Mulan',
            ],
        ];

        return $topPlayers[$category] ?? [];
    }

    /**
     * Get player icon path based on player name and category
     */
    private function getPlayerIconPath(?string $playerName, ?string $category): ?string
    {
        if (!$playerName || !$category) {
            return null;
        }

        // Map player names to exact icon file names
        $iconMaps = [
            'football' => [
                'Yamal' => 'LAMINE YAMAL.png',
                'Lamine Yamal' => 'LAMINE YAMAL.png',
                'Messi' => 'LIONEL MESSI.png',
                'Lionel Messi' => 'LIONEL MESSI.png',
                'Cristiano Ronaldo' => 'Cristiano Ronaldo.png',
                'Ronaldo' => 'Ronaldo.png',
                'Diego Maradona' => 'DIEGO MARADONA.png',
                'Maradona' => 'DIEGO MARADONA.png',
                'Rodrigo Mora' => 'Rodrigo Mora.png',
                'Estevao Willian' => 'ESTEVAO WILLIAN.png',
                'Franco Mastantuono' => 'FRANCO MASTANTUONO.png',
                'Desire Doue' => 'DESIRE DOUE.png',
                'Erling Haaland' => 'ERLING HAALAND.png',
                'Haaland' => 'ERLING HAALAND.png',
                'Kylian Mbappe' => 'KYLIAN MBAPPE.png',
                'Kylian Mbappé' => 'KYLIAN MBAPPE.png',
                'Mbappe' => 'KYLIAN MBAPPE.png',
                'Roberto Lewandowski' => 'ROBERT LEWANDOWSKI.png',
                'Robert Lewandowski' => 'ROBERT LEWANDOWSKI.png',
                'Lewandowski' => 'ROBERT LEWANDOWSKI.png',
            ],
            'basketball' => [
                'Cooper Flagg' => 'Cooper Flagg.png',
                'Viktor Wembanyama' => 'Viktor Wembanyama.png',
                'Michael Jordan' => 'Michael Jordan.png',
                'Anthony Edwards' => 'Anthony Edwards.png',
                'LeBron James' => 'LeBron James.png',
                'Luka Doncic' => 'Luka Doncic.png',
                'Nikola Jokic' => 'Nikola Jokic.png',
                'Stephen Curry' => 'Stephen Curry.png',
                'Zaccharie Risacher' => 'Zaccharie Risacher.png',
                'Kobe Bryant' => 'Kobe Bryant.png',
            ],
            'disney' => [
                'Mickey Mouse' => 'MickeyMouse.png',
                'Elsa' => 'Elsa.png',
                'Donald Duck' => 'DonaldDuck.png',
                'Genie' => 'Genie.png',
                'Stitch' => 'Stitch.png',
                'Whitesnow' => 'Whitesnow.png',
                'Ariel' => 'Ariel.png',
                'Belle' => 'Belle.png',
                'Cinderella' => 'Cinderella.png',
                'Mulan' => 'Mulan.png',
            ],
        ];

        $categoryMap = $iconMaps[$category] ?? null;
        if (!$categoryMap) {
            return null;
        }

        // Determine folder name based on category
        $folderName = 'Top Player - Football';
        if ($category === 'disney') {
            $folderName = 'Top Character - Disney';
        } elseif ($category === 'basketball') {
            $folderName = 'Top Player - Basketball';
        }

        // Try exact match first
        if (isset($categoryMap[$playerName])) {
            return "/images/icons/{$folderName}/{$categoryMap[$playerName]}";
        }

        // Try case-insensitive and partial match
        $playerNameLower = strtolower(trim($playerName));
        foreach ($categoryMap as $key => $fileName) {
            if (strtolower($key) === $playerNameLower || 
                strpos($playerNameLower, strtolower($key)) !== false || 
                strpos(strtolower($key), $playerNameLower) !== false) {
                return "/images/icons/{$folderName}/{$fileName}";
            }
        }

        return null;
    }

    /**
     * Get team name from card data
     */
    /**
     * Order top players by specific priority list
     */
    private function orderTopPlayers($cards, $category)
    {
        // Define player priority order for each category
        $playerOrder = [
            'football' => [
                'Yamal' => 1,
                'Lamine Yamal' => 1,
                'Messi' => 2,
                'Lionel Messi' => 2,
                'Cristiano Ronaldo' => 3,
                'Ronaldo' => 4,
                'Diego Maradona' => 5,
                'Maradona' => 5,
                'Rodrigo Mora' => 6,
                'Estevao Willian' => 7,
                'Franco Mastantuono' => 8,
                'Desire Doue' => 9,
                'Erling Haaland' => 10,
                'Haaland' => 10,
                'Kylian Mbappé' => 11,
                'Kylian Mbappe' => 11,
                'Mbappe' => 11,
                'Roberto Lewandowski' => 12,
                'Robert Lewandowski' => 12,
                'Lewandowski' => 12,
            ],
            'basketball' => [
                'Cooper Flagg' => 1,
                'Viktor Wembanyama' => 2,
                'Wembanyama' => 2,
                'Michael Jordan' => 3,
                'Jordan' => 3,
                'Anthony Edwards' => 4,
                'LeBron James' => 5,
                'Lebron James' => 5,
                'James' => 5,
                'Luka Doncic' => 6,
                'Doncic' => 6,
                'Nikola Jokic' => 7,
                'Jokic' => 7,
                'Stephen Curry' => 8,
                'Curry' => 8,
                'Zaccharie Risacher' => 9,
                'Kobe Bryant' => 10,
                'Bryant' => 10,
            ],
            'disney' => [
                'Mickey Mouse' => 1,
                'Mickey' => 1,
                'Elsa' => 2,
                'Donald Duck' => 3,
                'Donald' => 3,
                'Genie' => 4,
                'Stitch' => 5,
                'Whitesnow' => 6,
                'White Snow' => 6,
                'Snow White' => 6,
                'Ariel' => 7,
                'Belle' => 8,
                'Cinderella' => 9,
                'Mulan' => 10,
            ],
        ];

        if (!isset($playerOrder[$category])) {
            return $cards;
        }

        $orderMap = $playerOrder[$category];
        $maxPriority = 999; // Default priority for players not in the list

        return $cards->sortBy(function ($card) use ($orderMap, $maxPriority) {
            $playerName = $card->player->name ?? null;
            if (!$playerName) {
                return $maxPriority;
            }

            // Try exact match first
            if (isset($orderMap[$playerName])) {
                return $orderMap[$playerName];
            }

            // Try case-insensitive match
            $playerNameLower = strtolower($playerName);
            foreach ($orderMap as $key => $priority) {
                if (strtolower($key) === $playerNameLower) {
                    return $priority;
                }
            }

            // Try partial match
            foreach ($orderMap as $key => $priority) {
                if (stripos($playerName, $key) !== false || stripos($key, $playerName) !== false) {
                    return $priority;
                }
            }

            return $maxPriority;
        })->values();
    }

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
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->where('name', '!=', 'Player')
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
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->where('name', '!=', 'Player')
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
                
                // Prendi l'immagine dalla prima listing attiva, altrimenti usa quella del card model
                $imageUrl = $card->image_url;
                $listingId = null;
                
                // Cerca la prima listing attiva per questa carta
                $activeListing = $card->cardListings()
                    ->where('status', 'active')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($activeListing) {
                    $listingId = $activeListing->id;
                    
                    // Priorità all'immagine della listing
                    if ($activeListing->images && is_array($activeListing->images) && count($activeListing->images) > 0) {
                        $firstImage = $activeListing->images[0];
                        if (!str_starts_with($firstImage, '/storage/') && !str_starts_with($firstImage, 'http')) {
                            $imageUrl = '/storage/' . $firstImage;
                        } else {
                            $imageUrl = $firstImage;
                        }
                    }
                }
                
                return [
                    'id' => $card->id,
                    'name' => $cardName,
                    'team' => $this->getTeamName($card),
                    'type' => $this->getCategoryType($card->category->name ?? ''),
                    'price' => $this->getEstimatedPrice($card),
                    'rating' => $this->getEstimatedRating($card),
                    'image_url' => $imageUrl,
                    'set_name' => $card->set_name,
                    'year' => $card->year,
                    'rarity' => $card->rarity,
                    'slug' => $card->slug,
                    'category_slug' => $this->getCategorySlug($card->category->slug ?? ''),
                    'listing_id' => $listingId, // Aggiunto per la navigazione
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
            // Mostra solo carte che hanno almeno una listing attiva
            $relatedQuery = CardModel::with(['category', 'player', 'team', 'cardSet'])
                ->where('id', '!=', $mainCard->id) // Esclude la carta principale
                ->where('is_active', true)
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->where('name', '!=', 'Player')
                ->whereHas('cardListings', function($q) {
                    $q->where('status', 'active');
                });

            // Applica filtri basati sui criteri di similarità
            $this->applyRelatedFilters($relatedQuery, $mainCard);

            // Determina se è una categoria senza player (Disney, Spongebob)
            $isNonPlayerCategory = in_array($mainCard->category->slug ?? '', ['disney', 'spongebob']);
            
            // Estrai il nome base per Disney/Spongebob (prima del " - ")
            $mainCardBaseName = null;
            if ($isNonPlayerCategory && $mainCard->name) {
                $parts = explode(' - ', $mainCard->name);
                $mainCardBaseName = $parts[0] ?? null;
            }
            
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
            
            // Debug: conta quante carte ci sono con e senza listing attive
            $totalCardsInCategory = CardModel::where('category_id', $mainCard->category_id)
                ->where('is_active', true)
                ->count();
            $cardsWithListings = CardModel::where('category_id', $mainCard->category_id)
                ->where('is_active', true)
                ->whereHas('cardListings', function($q) {
                    $q->where('status', 'active');
                })
                ->count();
            
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
                'total_cards_in_category' => $totalCardsInCategory,
                'cards_with_active_listings' => $cardsWithListings,
                'main_card_has_listings' => $mainCard->cardListings()->where('status', 'active')->exists(),
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
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->where('name', '!=', 'Player')
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
                
                // Prendi l'immagine dalla prima listing attiva, altrimenti usa quella del card model
                $imageUrl = $card->image_url;
                $listingId = null;
                
                // Cerca la prima listing attiva per questa carta
                $activeListing = $card->cardListings()
                    ->where('status', 'active')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($activeListing) {
                    $listingId = $activeListing->id;
                    
                    // Priorità all'immagine della listing
                    if ($activeListing->images && is_array($activeListing->images) && count($activeListing->images) > 0) {
                        $firstImage = $activeListing->images[0];
                        if (!str_starts_with($firstImage, '/storage/') && !str_starts_with($firstImage, 'http')) {
                            $imageUrl = '/storage/' . $firstImage;
                        } else {
                            $imageUrl = $firstImage;
                        }
                    }
                }
                
                return [
                    'id' => $card->id,
                    'name' => $cardName,
                    'team' => $this->getTeamName($card),
                    'type' => $this->getCategoryType($card->category->name ?? ''),
                    'price' => $this->getEstimatedPrice($card),
                    'rating' => $this->getEstimatedRating($card),
                    'image_url' => $imageUrl,
                    'set_name' => $card->set_name,
                    'year' => $card->year,
                    'rarity' => $card->rarity,
                    'slug' => $card->slug,
                    'category_slug' => $this->getCategorySlug($card->category->slug ?? ''),
                    'listing_id' => $listingId, // Aggiunto per la navigazione
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
