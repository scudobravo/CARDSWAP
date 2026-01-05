<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use App\Services\AvailabilityService;
use App\Services\StripeErrorService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\CardListing;
use App\Models\ShippingZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private StripeService $stripeService;
    private AvailabilityService $availabilityService;
    private StripeErrorService $stripeErrorService;

    public function __construct(StripeService $stripeService, AvailabilityService $availabilityService, StripeErrorService $stripeErrorService)
    {
        $this->stripeService = $stripeService;
        $this->availabilityService = $availabilityService;
        $this->stripeErrorService = $stripeErrorService;
    }

    /**
     * Crea un pagamento per un ordine multi-venditore
     */
    public function createPayment(Request $request): JsonResponse
    {
        // Trasforma i dati dal formato frontend al formato backend se necessario
        $requestData = $this->transformRequestData($request->all());
        
        // Se la trasformazione ha restituito un errore
        if (isset($requestData['_error'])) {
            return response()->json([
                'success' => false,
                'message' => $requestData['_error'],
                'errors' => ['shipping_zone' => [$requestData['_error']]]
            ], 422);
        }
        
        // Log per debug
        \Log::info('Payment create request', [
            'original_data_keys' => array_keys($request->all()),
            'transformed_data_keys' => array_keys($requestData),
            'has_sellers' => isset($requestData['sellers']),
            'has_shipping_address' => isset($requestData['shipping_address']),
            'sellers_count' => isset($requestData['sellers']) ? count($requestData['sellers']) : 0,
        ]);
        
        $validator = Validator::make($requestData, [
            'sellers' => 'required|array|min:1',
            'sellers.*.seller_id' => 'required|integer|exists:users,id',
            'sellers.*.items' => 'required|array|min:1',
            'sellers.*.items.*.listing_id' => 'required|integer|exists:card_listings,id',
            'sellers.*.items.*.quantity' => 'required|integer|min:1',
            'sellers.*.shipping_zone_id' => 'required|integer|exists:shipping_zones,id',
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string|max:255',
            'shipping_address.last_name' => 'required|string|max:255',
            'shipping_address.address_line_1' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.postal_code' => 'required|string|max:10',
            'shipping_address.country' => 'required|string|max:2',
        ]);

        if ($validator->fails()) {
            \Log::error('Payment validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data_keys' => array_keys($requestData),
                'has_sellers' => isset($requestData['sellers']),
                'has_shipping_address' => isset($requestData['shipping_address']),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Dati di validazione non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $buyer = Auth::user();
            
            // Validazione completa prima del pagamento
            $validationResult = $this->validateOrderData($requestData, $buyer);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore di validazione ordine',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            $orderData = $this->prepareOrderData($requestData, $buyer);
            
            // Crea l'ordine nel database
            $order = $this->createOrder($orderData);
            
            // Prepara i dati per Stripe
            $stripeData = $this->prepareStripeData($orderData, $order);
            
            // Crea il pagamento con Stripe
            $paymentResult = $this->stripeService->createMultiVendorPayment($stripeData);
            
            if (!$paymentResult['success']) {
                DB::rollBack();
                
                // Gestisci errori Stripe specifici
                if (isset($paymentResult['stripe_error'])) {
                    $errorDetails = $this->stripeErrorService->handleStripeError($paymentResult['stripe_error']);
                    $suggestions = $this->stripeErrorService->getActionSuggestions($errorDetails['action']);
                    
                    return response()->json([
                        'success' => false,
                        'message' => $errorDetails['user_message'],
                        'error_type' => 'stripe_error',
                        'action' => $errorDetails['action'],
                        'retry' => $errorDetails['retry'],
                        'suggestions' => $suggestions,
                        'technical_message' => $errorDetails['technical_message'] ?? null
                    ], 400);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Errore nella creazione del pagamento',
                    'error' => $paymentResult['error']
                ], 500);
            }

            // Aggiorna l'ordine con i dati del pagamento
            $order->update([
                'stripe_payment_intent_id' => $paymentResult['payment_intent']->id,
                'status' => 'pending_payment'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pagamento creato con successo',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'client_secret' => $paymentResult['client_secret'],
                    'total_amount' => $order->total_amount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log dell'errore completo per debugging
            \Log::error('Errore in createPayment: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore interno del server',
                'error' => config('app.debug') ? $e->getMessage() : 'Si è verificato un errore durante la creazione del pagamento'
            ], 500);
        }
    }

    /**
     * Conferma ordine prima del pagamento
     */
    public function confirmOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sellers' => 'required|array|min:1',
            'sellers.*.seller_id' => 'required|integer|exists:users,id',
            'sellers.*.items' => 'required|array|min:1',
            'sellers.*.items.*.listing_id' => 'required|integer|exists:card_listings,id',
            'sellers.*.items.*.quantity' => 'required|integer|min:1',
            'sellers.*.shipping_zone_id' => 'required|integer|exists:shipping_zones,id',
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string|max:255',
            'shipping_address.last_name' => 'required|string|max:255',
            'shipping_address.address_line_1' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.postal_code' => 'required|string|max:10',
            'shipping_address.country' => 'required|string|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati di validazione non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $buyer = Auth::user();
            
            // Validazione completa
            $validationResult = $this->validateOrderData($request->all(), $buyer);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore di validazione ordine',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            // Prepara i dati dell'ordine per la conferma
            $orderData = $this->prepareOrderData($request->all(), $buyer);
            
            // Calcola dettagli finali
            $orderSummary = $this->prepareOrderSummary($orderData);
            
            // Prenota temporaneamente le quantità
            $items = [];
            foreach ($orderData['sellers'] as $sellerData) {
                foreach ($sellerData['items'] as $item) {
                    $items[] = [
                        'listing_id' => $item['listing_id'],
                        'quantity' => $item['quantity']
                    ];
                }
            }
            
            $reservationResult = $this->availabilityService->reserveQuantities($items, 15);
            if (!$reservationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore nella prenotazione delle quantità',
                    'error' => $reservationResult['reason']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ordine confermato e pronto per il pagamento',
                'data' => [
                    'order_summary' => $orderSummary,
                    'reservation_id' => $reservationResult['reservation_id'],
                    'reservation_expires_at' => $reservationResult['expires_at'],
                    'payment_ready' => true
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella conferma dell\'ordine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trasforma i dati dal formato frontend al formato backend
     */
    private function transformRequestData(array $requestData): array
    {
        // Se i dati sono già nel formato corretto (sellers), restituiscili così
        if (isset($requestData['sellers']) && isset($requestData['shipping_address'])) {
            return $requestData;
        }

        // Trasforma dal formato frontend (cart_data + address) al formato backend (sellers + shipping_address)
        if (isset($requestData['cart_data']) && isset($requestData['address'])) {
            $sellers = [];
            $cartData = $requestData['cart_data'];
            $shippingMethods = $requestData['shipping_methods'] ?? [];
            $selectedShippingZones = $requestData['selected_shipping_zones'] ?? []; // Supporta anche questo formato

            foreach ($cartData as $sellerId => $items) {
                if (empty($items)) {
                    continue;
                }

                $sellerItems = [];
                
                foreach ($items as $item) {
                    $listingId = $item['id'] ?? $item['listing_id'] ?? null;
                    if (!$listingId) {
                        continue;
                    }
                    
                    $sellerItems[] = [
                        'listing_id' => (int) $listingId,
                        'quantity' => (int) ($item['quantity'] ?? 1),
                    ];
                }

                if (empty($sellerItems)) {
                    continue;
                }

                // Determina shipping_zone_id
                $shippingZoneId = null;
                
                // Prova prima con selected_shipping_zones (formato cart store)
                if (isset($selectedShippingZones[$sellerId])) {
                    $zoneId = $selectedShippingZones[$sellerId];
                    // Verifica che esista nel database
                    $zone = \App\Models\ShippingZone::find($zoneId);
                    if ($zone && $zone->is_active) {
                        $shippingZoneId = (int) $zoneId;
                        \Log::info('Shipping zone found from selected_shipping_zones', [
                            'seller_id' => $sellerId,
                            'zone_id' => $shippingZoneId,
                        ]);
                    }
                }
                
                // Poi prova con shipping_methods
                if (!$shippingZoneId && isset($shippingMethods[$sellerId])) {
                    $methodValue = $shippingMethods[$sellerId];
                    // Se è numerico, verifica se è un ID zona valido
                    if (is_numeric($methodValue)) {
                        $zone = \App\Models\ShippingZone::find((int) $methodValue);
                        if ($zone && $zone->is_active) {
                            $shippingZoneId = $zone->id;
                            \Log::info('Shipping zone found from shipping_methods (numeric)', [
                                'seller_id' => $sellerId,
                                'zone_id' => $shippingZoneId,
                                'method_value' => $methodValue,
                            ]);
                        }
                    }
                    // Se è un metodo (es: 'standard', 'express') o un ID Shippo, 
                    // cerca la prima zona disponibile per il venditore o le sue listing
                }

                // Se ancora non abbiamo una zona, cerca dalla prima listing
                if (!$shippingZoneId && !empty($sellerItems)) {
                    $firstListing = \App\Models\CardListing::with('shippingZones')->find($sellerItems[0]['listing_id']);
                    if ($firstListing && $firstListing->shippingZones->isNotEmpty()) {
                        // Cerca prima una zona attiva
                        $activeZone = $firstListing->shippingZones->firstWhere('is_active', true);
                        if ($activeZone) {
                            $shippingZoneId = $activeZone->id;
                            \Log::info('Shipping zone found from listing (active)', [
                                'seller_id' => $sellerId,
                                'listing_id' => $firstListing->id,
                                'zone_id' => $shippingZoneId,
                            ]);
                        } else {
                            // Se non c'è una zona attiva, usa la prima disponibile
                            $shippingZone = $firstListing->shippingZones->first();
                            $shippingZoneId = $shippingZone->id;
                            \Log::info('Shipping zone found from listing (first available)', [
                                'seller_id' => $sellerId,
                                'listing_id' => $firstListing->id,
                                'zone_id' => $shippingZoneId,
                            ]);
                        }
                    }
                }

                // Se ancora non abbiamo una zona, cerca zone del venditore
                if (!$shippingZoneId) {
                    $sellerZone = \App\Models\ShippingZone::where('user_id', $sellerId)
                        ->where('is_active', true)
                        ->first();
                    if ($sellerZone) {
                        $shippingZoneId = $sellerZone->id;
                        \Log::info('Shipping zone found from seller zones', [
                            'seller_id' => $sellerId,
                            'zone_id' => $shippingZoneId,
                        ]);
                    }
                }

                // Se ancora non abbiamo una zona, cerca zone globali (senza user_id)
                if (!$shippingZoneId) {
                    $globalZone = \App\Models\ShippingZone::whereNull('user_id')
                        ->where('is_active', true)
                        ->first();
                    if ($globalZone) {
                        $shippingZoneId = $globalZone->id;
                        \Log::info('Shipping zone found from global zones', [
                            'seller_id' => $sellerId,
                            'zone_id' => $shippingZoneId,
                        ]);
                    }
                }

                // Ultimo fallback: qualsiasi zona attiva
                if (!$shippingZoneId) {
                    $defaultZone = \App\Models\ShippingZone::where('is_active', true)->first();
                    if ($defaultZone) {
                        $shippingZoneId = $defaultZone->id;
                        \Log::warning('Using fallback shipping zone', [
                            'seller_id' => $sellerId,
                            'zone_id' => $shippingZoneId,
                            'shipping_methods' => $shippingMethods[$sellerId] ?? null,
                            'selected_shipping_zones' => $selectedShippingZones[$sellerId] ?? null,
                        ]);
                    }
                }

                // Se abbiamo una zona, aggiungi il venditore
                if ($shippingZoneId) {
                    $sellers[] = [
                        'seller_id' => (int) $sellerId,
                        'items' => $sellerItems,
                        'shipping_zone_id' => $shippingZoneId,
                    ];
                    \Log::info('Seller added to payment request', [
                        'seller_id' => $sellerId,
                        'zone_id' => $shippingZoneId,
                        'items_count' => count($sellerItems),
                    ]);
                } else {
                    // Log errore critico se non troviamo una zona
                    \Log::error('Shipping zone not found for seller - CRITICAL', [
                        'seller_id' => $sellerId,
                        'shipping_methods' => $shippingMethods[$sellerId] ?? null,
                        'selected_shipping_zones' => $selectedShippingZones[$sellerId] ?? null,
                        'first_listing_id' => $sellerItems[0]['listing_id'] ?? null,
                        'total_active_zones' => \App\Models\ShippingZone::where('is_active', true)->count(),
                    ]);
                }
            }
            
            // Se non abbiamo trovato nessun venditore valido, restituisci errore dettagliato
            if (empty($sellers)) {
                \Log::error('No valid sellers found after transformation', [
                    'cart_data_keys' => array_keys($cartData),
                    'cart_data_structure' => array_map(function($items) {
                        return [
                            'items_count' => count($items),
                            'first_item' => $items[0] ?? null,
                        ];
                    }, $cartData),
                    'shipping_methods' => $shippingMethods,
                    'selected_shipping_zones' => $selectedShippingZones,
                    'total_active_zones' => \App\Models\ShippingZone::where('is_active', true)->count(),
                    'zones_by_user' => \App\Models\ShippingZone::where('is_active', true)
                        ->select('user_id', DB::raw('count(*) as count'))
                        ->groupBy('user_id')
                        ->get()
                        ->toArray(),
                ]);
                
                return [
                    'sellers' => [],
                    'shipping_address' => $shippingAddress ?? [],
                    '_error' => 'Nessuna zona di spedizione disponibile per i venditori selezionati. Verifica che i venditori abbiano zone di spedizione configurate.',
                ];
            }

            // Trasforma address in shipping_address
            $address = $requestData['address'];
            $shippingAddress = [
                'first_name' => $address['first_name'] ?? '',
                'last_name' => $address['last_name'] ?? '',
                'address_line_1' => $address['address_line_1'] ?? $address['address'] ?? '',
                'address_line_2' => $address['address_line_2'] ?? $address['apartment'] ?? null,
                'city' => $address['city'] ?? '',
                'state_province' => $address['state_province'] ?? $address['region'] ?? null,
                'postal_code' => $address['postal_code'] ?? $address['postalCode'] ?? '',
                'country' => $address['country'] ?? 'IT',
                'phone' => $address['phone'] ?? null,
            ];

            return [
                'sellers' => $sellers,
                'shipping_address' => $shippingAddress,
            ];
        }

        // Se non riusciamo a trasformare, restituisci i dati originali
        // La validazione fallirà e mostrerà l'errore appropriato
        return $requestData;
    }

    /**
     * Prepara i dati dell'ordine
     */
    private function prepareOrderData(array $requestData, User $buyer): array
    {
        $sellers = $requestData['sellers'];
        $subtotal = 0;
        $totalShippingCost = 0;
        $orderItems = [];

        foreach ($sellers as $sellerData) {
            $seller = User::find($sellerData['seller_id']);
            $sellerSubtotal = 0;
            $shippingCost = 0;

            // Calcola subtotale venditore
            foreach ($sellerData['items'] as $itemData) {
                $listing = \App\Models\CardListing::find($itemData['listing_id']);
                $itemTotal = $listing->price * $itemData['quantity'];
                $sellerSubtotal += $itemTotal;

                $orderItems[] = [
                    'card_listing_id' => $listing->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $listing->price,
                    'total_price' => $itemTotal,
                    'condition' => $listing->condition,
                    'seller_id' => $seller->id
                ];
            }

            // Calcola costo spedizione
            $shippingZone = \App\Models\ShippingZone::find($sellerData['shipping_zone_id']);
            $shippingCost = $listing->getShippingCostForZone($sellerData['shipping_zone_id']);
            $totalShippingCost += $shippingCost;
            $subtotal += $sellerSubtotal;
        }

        // Calcola tassa acquirente (costo di gestione): 1.5% sul subtotale
        $taxAmount = $subtotal * 0.015;
        
        // Totale = subtotale + spedizione + tassa acquirente
        $totalAmount = $subtotal + $totalShippingCost + $taxAmount;

        return [
            'buyer' => $buyer,
            'sellers' => $sellers,
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
            'total_shipping_cost' => $totalShippingCost,
            'tax_amount' => $taxAmount,
            'shipping_address' => $requestData['shipping_address'],
            'order_items' => $orderItems
        ];
    }

    /**
     * Crea l'ordine nel database
     */
    private function createOrder(array $orderData): Order
    {
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT);

        $order = Order::create([
            'order_number' => $orderNumber,
            'buyer_id' => $orderData['buyer']->id,
            'seller_id' => $orderData['sellers'][0]['seller_id'], // Primo venditore come principale
            'status' => 'pending',
            'subtotal' => $orderData['subtotal'] ?? ($orderData['total_amount'] - $orderData['total_shipping_cost'] - ($orderData['tax_amount'] ?? 0)),
            'shipping_cost' => $orderData['total_shipping_cost'],
            'tax_amount' => $orderData['tax_amount'] ?? 0, // Costo di gestione (1.5% sul subtotale)
            'total_amount' => $orderData['total_amount'],
            'shipping_address' => $orderData['shipping_address'],
            'billing_address' => $orderData['shipping_address'], // Per ora uguale
        ]);

        // Crea gli OrderItem
        foreach ($orderData['order_items'] as $itemData) {
            OrderItem::create([
                'order_id' => $order->id,
                'card_listing_id' => $itemData['card_listing_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total_price' => $itemData['total_price'],
                'condition' => $itemData['condition']
            ]);
        }

        return $order;
    }

    /**
     * Prepara i dati per Stripe
     */
    private function prepareStripeData(array $orderData, Order $order): array
    {
        $sellers = [];
        $totalSellerAmount = 0;

        foreach ($orderData['sellers'] as $sellerData) {
            $seller = User::find($sellerData['seller_id']);
            
            if (!$seller) {
                throw new \Exception("Venditore con ID {$sellerData['seller_id']} non trovato");
            }
            
            if (!$seller->stripe_account_id) {
                throw new \Exception("Venditore {$seller->name} non ha un account Stripe configurato");
            }

            $sellerAmount = 0;
            foreach ($sellerData['items'] as $itemData) {
                $listing = \App\Models\CardListing::find($itemData['listing_id']);
                if (!$listing) {
                    throw new \Exception("Inserzione con ID {$itemData['listing_id']} non trovata");
                }
                $sellerAmount += $listing->price * $itemData['quantity'];
            }

            // La spedizione viene gestita tramite Shippo e non viene data al venditore
            // Il venditore riceve solo il 94% del subtotale
            $totalSellerAmount += $sellerAmount; // Solo subtotale, senza spedizione

            $sellers[] = [
                'seller_id' => $seller->id,
                'stripe_account_id' => $seller->stripe_account_id,
                'amount' => $sellerAmount // Solo subtotale per il calcolo della commissione
            ];
        }

        // Calcola la commissione venditore solo sul subtotale (senza spedizione), non sul totale che include spedizione e tassa acquirente
        // La spedizione viene gestita tramite Shippo e non viene data al venditore
        // Con Stripe Connect, il venditore riceve: amount - application_fee_amount
        // Per far ricevere al venditore esattamente il 94% del subtotale:
        // application_fee = amount - (subtotale * 0.94)
        // = (subtotale + spedizione + buyer_tax) - (subtotale * 0.94)
        // = subtotale * 0.06 + spedizione + buyer_tax
        $buyerTax = $orderData['total_amount'] - $totalSellerAmount; // Spedizione + Tassa acquirente (1.5%)
        $applicationFee = ($totalSellerAmount * 0.06) + $buyerTax;
        
        // NOTA: Oltre a queste commissioni, ci saranno anche:
        // - Trattenuta Stripe: ~3,5% + 0,30€ (dedotta automaticamente da Stripe sul totale pagato)
        // - Trattenuta Shippo: costo spedizione (dedotta quando viene creato il label di spedizione)
        // Il netto per CardSwap sarà: 6% + 1,5% + spedizione - costi Stripe - costi Shippo

        return [
            'order_id' => $order->id,
            'buyer_id' => $orderData['buyer']->id,
            'total_amount' => $orderData['total_amount'],
            'application_fee' => $applicationFee,
            'currency' => 'eur',
            'sellers' => $sellers,
            'metadata' => [
                'order_number' => $order->order_number,
                'buyer_email' => $orderData['buyer']->email
            ]
        ];
    }

    /**
     * Validazione completa dei dati ordine
     */
    private function validateOrderData(array $requestData, User $buyer): array
    {
        $errors = [];
        $sellers = $requestData['sellers'];

        // Verifica disponibilità real-time
        $items = [];
        foreach ($sellers as $sellerData) {
            foreach ($sellerData['items'] as $item) {
                $items[] = [
                    'listing_id' => $item['listing_id'],
                    'quantity' => $item['quantity']
                ];
            }
        }

        $availabilityResult = $this->availabilityService->checkMultipleListingsAvailability($items);
        if (!$availabilityResult['all_available']) {
            $errors['availability'] = 'Alcuni articoli non sono più disponibili';
            foreach ($availabilityResult['items'] as $listingId => $result) {
                if (!$result['available']) {
                    $errors['items'][$listingId] = $result['reason'];
                }
            }
        }

        // Verifica venditori e zone di spedizione
        foreach ($sellers as $sellerData) {
            $seller = User::find($sellerData['seller_id']);
            if (!$seller) {
                $errors['sellers'][] = "Venditore {$sellerData['seller_id']} non trovato";
                continue;
            }

            // Aggiorna lo stato Stripe Connect da Stripe prima di verificare
            // Questo assicura che abbiamo lo stato più recente
            if ($seller->hasStripeAccount()) {
                try {
                    $stripeService = app(\App\Services\StripeService::class);
                    $accountStatus = $stripeService->getConnectAccount($seller->stripe_account_id);
                    
                    if ($accountStatus['success']) {
                        // Aggiorna lo stato locale con i dati più recenti da Stripe
                        $seller->update([
                            'stripe_charges_enabled' => $accountStatus['charges_enabled'],
                            'stripe_payouts_enabled' => $accountStatus['payouts_enabled'],
                            'stripe_details_submitted' => $accountStatus['details_submitted'],
                        ]);
                        // Ricarica il modello per avere i valori aggiornati
                        $seller->refresh();
                    }
                } catch (\Exception $e) {
                    \Log::warning("Errore nell'aggiornamento stato Stripe per venditore {$seller->id}: " . $e->getMessage());
                    // Continua con la verifica anche se l'aggiornamento fallisce
                }
            }

            // Verifica se il venditore può ricevere pagamenti
            if (!$seller->canSellWithStripe()) {
                if (!$seller->isSeller()) {
                    $errors['sellers'][] = "Il venditore {$seller->name} non ha il ruolo di venditore configurato. Contatta il supporto per risolvere il problema.";
                } elseif (!$seller->hasStripeAccount()) {
                    $errors['sellers'][] = "Il venditore {$seller->name} non ha configurato Stripe Connect. L'inserzione non può essere acquistata finché il venditore non completa la configurazione.";
                } elseif (!$seller->stripe_charges_enabled || !$seller->stripe_payouts_enabled) {
                    $errors['sellers'][] = "Il venditore {$seller->name} non ha completato la configurazione di Stripe Connect. Deve completare l'onboarding su Stripe per poter ricevere pagamenti. Vai su Account > Metodi di Pagamento per completare la configurazione.";
                } elseif (!$seller->hasCompletedKyc() && !$seller->hasStripeIdentityVerified()) {
                    $errors['sellers'][] = "Il venditore {$seller->name} non ha completato la verifica KYC. Deve completare la verifica dell'identità prima di poter vendere.";
                } else {
                    $errors['sellers'][] = "Il venditore {$seller->name} non può ricevere pagamenti al momento. Contatta il supporto per maggiori informazioni.";
                }
            }

            // Verifica zone di spedizione
            $shippingZone = ShippingZone::find($sellerData['shipping_zone_id']);
            if (!$shippingZone) {
                $errors['shipping'][] = "Zona di spedizione non trovata per venditore {$seller->name}";
                continue;
            }

            // Verifica se il venditore può usare questa zona
            if (!$shippingZone->canBeUsedBySeller($seller)) {
                $errors['shipping'][] = "Venditore {$seller->name} non può usare questa zona di spedizione";
            }

            // Verifica prezzi e calcoli
            $sellerSubtotal = 0;
            foreach ($sellerData['items'] as $itemData) {
                $listing = CardListing::find($itemData['listing_id']);
                if (!$listing) {
                    $errors['items'][] = "Inserzione {$itemData['listing_id']} non trovata";
                    continue;
                }

                // Verifica che l'inserzione appartenga al venditore
                if ($listing->seller_id != $seller->id) {
                    $errors['items'][] = "Inserzione {$itemData['listing_id']} non appartiene al venditore {$seller->name}";
                }

                // Verifica prezzo
                $itemTotal = $listing->price * $itemData['quantity'];
                $sellerSubtotal += $itemTotal;
            }

            // Verifica calcolo spedizione
            try {
                $orderWeight = $shippingZone->calculateOrderWeight($sellerData['items']);
                $shippingCost = $shippingZone->calculateShippingCost($sellerSubtotal, $orderWeight);
            } catch (\Exception $e) {
                $errors['shipping'][] = "Errore calcolo spedizione per venditore {$seller->name}: " . $e->getMessage();
            }
        }

        // Verifica indirizzo di spedizione
        $shippingAddress = $requestData['shipping_address'];
        if (empty($shippingAddress['first_name']) || empty($shippingAddress['last_name']) ||
            empty($shippingAddress['address_line_1']) || empty($shippingAddress['city']) ||
            empty($shippingAddress['postal_code']) || empty($shippingAddress['country'])) {
            $errors['shipping_address'] = 'Indirizzo di spedizione incompleto';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Prepara il riepilogo dettagliato dell'ordine
     */
    private function prepareOrderSummary(array $orderData): array
    {
        $sellers = [];
        $totalAmount = 0;
        $totalShippingCost = 0;

        foreach ($orderData['sellers'] as $sellerData) {
            $seller = User::find($sellerData['seller_id']);
            $sellerSubtotal = 0;
            $items = [];

            foreach ($sellerData['items'] as $itemData) {
                $listing = CardListing::find($itemData['listing_id']);
                $itemTotal = $listing->price * $itemData['quantity'];
                $sellerSubtotal += $itemTotal;

                $items[] = [
                    'listing_id' => $listing->id,
                    'card_model' => $listing->cardModel->name ?? 'Carta',
                    'condition' => $listing->condition,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $listing->price,
                    'total_price' => $itemTotal
                ];
            }

            $shippingZone = ShippingZone::find($sellerData['shipping_zone_id']);
            $orderWeight = $shippingZone->calculateOrderWeight($sellerData['items']);
            $shippingCost = $shippingZone->calculateShippingCost($sellerSubtotal, $orderWeight);
            $totalShippingCost += $shippingCost;

            $sellerTotal = $sellerSubtotal + $shippingCost;
            $totalAmount += $sellerTotal;

            $sellers[] = [
                'seller_id' => $seller->id,
                'seller_name' => $seller->name,
                'items' => $items,
                'subtotal' => $sellerSubtotal,
                'shipping_cost' => $shippingCost,
                'shipping_zone' => [
                    'id' => $shippingZone->id,
                    'name' => $shippingZone->name,
                    'delivery_days' => $shippingZone->delivery_days_min . '-' . $shippingZone->delivery_days_max
                ],
                'total' => $sellerTotal
            ];
        }

        return [
            'sellers' => $sellers,
            'subtotal' => $totalAmount - $totalShippingCost,
            'shipping_cost' => $totalShippingCost,
            'total_amount' => $totalAmount,
            'shipping_address' => $orderData['shipping_address'],
            'item_count' => count($orderData['order_items']),
            'seller_count' => count($sellers)
        ];
    }
}
