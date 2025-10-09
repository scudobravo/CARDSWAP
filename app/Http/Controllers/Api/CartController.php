<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardListing;
use App\Models\ShippingZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class CartController extends Controller
{
    /**
     * Get user's cart items
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $cartData = $request->get('cart_data', []);
            
            // Se l'utente non è autenticato, restituisci carrello vuoto
            if (!$user && empty($cartData)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Carrello vuoto'
                ]);
            }
            
            if (empty($cartData)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Carrello vuoto'
                ]);
            }

            $cartItems = [];
            $totalItems = 0;
            $subtotal = 0;

            foreach ($cartData as $sellerId => $items) {
                $sellerItems = [];
                
                foreach ($items as $item) {
                    $listing = CardListing::with([
                        'cardModel.category',
                        'cardModel.cardSet',
                        'cardModel.player',
                        'cardModel.team',
                        'cardModel.league',
                        'seller',
                        'shippingZones'
                    ])->find($item['id']);

                    if ($listing && $listing->status === 'active') {
                        $cartItem = [
                            'id' => $listing->id,
                            'card_model_id' => $listing->card_model_id,
                            'seller_id' => $listing->seller_id,
                            'price' => $listing->price,
                            'quantity' => $item['quantity'],
                            'condition' => $listing->condition,
                            'description' => $listing->description,
                            'images' => $listing->images,
                            'available' => $listing->quantity >= $item['quantity'],
                            'seller' => $listing->seller,
                            'cardModel' => $listing->cardModel,
                            'shippingZones' => $listing->shippingZones
                        ];

                        $sellerItems[] = $cartItem;
                        $totalItems += $item['quantity'];
                        $subtotal += $listing->price * $item['quantity'];
                    }
                }

                if (!empty($sellerItems)) {
                    $cartItems[$sellerId] = $sellerItems;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $cartItems,
                    'total_items' => $totalItems,
                    'subtotal' => $subtotal,
                    'sellers' => array_keys($cartItems)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero del carrello',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to cart
     */
    public function addItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|string', // Cambiato da exists:card_listings,id per supportare mock IDs
            'quantity' => 'required|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Per ora, gestiamo solo mock listings per il testing
            // In futuro, questo dovrebbe cercare nel database
            if (str_starts_with($request->listing_id, 'listing_')) {
                // Mock listing data
                $cartItem = [
                    'id' => $request->listing_id,
                    'card_model_id' => str_replace('listing_', '', $request->listing_id),
                    'seller_id' => 1,
                    'price' => 95.00,
                    'quantity' => $request->quantity,
                    'condition' => 'LIGHT PLAYED',
                    'description' => 'Carta in ottime condizioni',
                    'images' => [],
                    'available' => true,
                    'seller' => [
                        'id' => 1,
                        'name' => 'Venditore Mock',
                        'email' => 'vendor@example.com'
                    ],
                    'cardModel' => [
                        'id' => str_replace('listing_', '', $request->listing_id),
                        'name' => 'Mock Card',
                        'category' => 'football'
                    ],
                    'shippingZones' => []
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Articolo aggiunto al carrello',
                    'data' => $cartItem
                ]);
            }

            // Per listing reali dal database (quando implementato)
            $listing = CardListing::with([
                'cardModel.category',
                'cardModel.cardSet',
                'cardModel.player',
                'cardModel.team',
                'cardModel.league',
                'seller',
                'shippingZones'
            ])->find($request->listing_id);

            if (!$listing || $listing->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Inserzione non disponibile'
                ], 404);
            }

            if ($listing->quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantità non disponibile'
                ], 400);
            }

            $cartItem = [
                'id' => $listing->id,
                'card_model_id' => $listing->card_model_id,
                'seller_id' => $listing->seller_id,
                'price' => $listing->price,
                'quantity' => $request->quantity,
                'condition' => $listing->condition,
                'description' => $listing->description,
                'images' => $listing->images,
                'available' => true,
                'seller' => $listing->seller,
                'cardModel' => $listing->cardModel,
                'shippingZones' => $listing->shippingZones
            ];

            return response()->json([
                'success' => true,
                'message' => 'Articolo aggiunto al carrello',
                'data' => $cartItem
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiunta al carrello',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update item quantity in cart
     */
    public function updateQuantity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|exists:card_listings,id',
            'quantity' => 'required|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $listing = CardListing::find($request->listing_id);

            if (!$listing || $listing->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Inserzione non disponibile'
                ], 404);
            }

            if ($listing->quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantità non disponibile'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Quantità aggiornata',
                'data' => [
                    'listing_id' => $listing->id,
                    'quantity' => $request->quantity,
                    'available' => $listing->quantity >= $request->quantity
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento della quantità',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|exists:card_listings,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $listing = CardListing::find($request->listing_id);

            if (!$listing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inserzione non trovata'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Articolo rimosso dal carrello',
                'data' => [
                    'listing_id' => $listing->id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la rimozione dal carrello',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipping costs for cart
     */
    public function getShippingCosts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cart_data' => 'required|array',
            'address_id' => 'nullable|exists:user_addresses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cartData = $request->cart_data;
            $shippingCosts = [];

            foreach ($cartData as $sellerId => $items) {
                $sellerShippingZones = [];
                
                // Raccogli tutte le zone di spedizione per questo venditore
                foreach ($items as $item) {
                    $listing = CardListing::with('shippingZones')->find($item['id']);
                    if ($listing) {
                        foreach ($listing->shippingZones as $zone) {
                            if (!isset($sellerShippingZones[$zone->id])) {
                                $sellerShippingZones[$zone->id] = $zone;
                            }
                        }
                    }
                }

                $shippingCosts[$sellerId] = $sellerShippingZones;
            }

            return response()->json([
                'success' => true,
                'data' => $shippingCosts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il calcolo dei costi di spedizione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate cart before checkout
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cart_data' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cartData = $request->cart_data;
            $errors = [];
            $warnings = [];

            foreach ($cartData as $sellerId => $items) {
                foreach ($items as $item) {
                    $listing = CardListing::find($item['id']);
                    
                    if (!$listing) {
                        $errors[] = "Inserzione {$item['id']} non trovata";
                        continue;
                    }

                    if ($listing->status !== 'active') {
                        $errors[] = "Inserzione {$listing->id} non più disponibile";
                        continue;
                    }

                    if ($listing->quantity < $item['quantity']) {
                        $errors[] = "Quantità non disponibile per l'inserzione {$listing->id}";
                        continue;
                    }

                    if ($listing->price != $item['price']) {
                        $warnings[] = "Prezzo aggiorato per l'inserzione {$listing->id}";
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => empty($errors),
                    'errors' => $errors,
                    'warnings' => $warnings
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la validazione del carrello',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear cart
     */
    public function clear(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Carrello svuotato'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante lo svuotamento del carrello',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
