<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use App\Services\AvailabilityService;
use App\Services\StripeErrorService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipping;
use App\Models\User;
use App\Models\CardListing;
use App\Models\ShippingZone;
use App\Events\ListingSold;
use App\Enums\ShippingMethod;
use App\Enums\ShippingPackageBucket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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
        Log::info('Payment create request', [
            'original_data_keys' => array_keys($request->all()),
            'transformed_data_keys' => array_keys($requestData),
            'has_sellers' => isset($requestData['sellers']),
            'has_shipping_address' => isset($requestData['shipping_address']),
            'sellers_count' => isset($requestData['sellers']) ? count($requestData['sellers']) : 0,
        ]);
        
        // Regole di validazione - CardSwap Shipping V1 obbligatorio
        $validationRules = [
            'sellers' => 'required|array|min:1',
            'sellers.*.seller_id' => 'required|integer|exists:users,id',
            'sellers.*.items' => 'required|array|min:1',
            'sellers.*.items.*.listing_id' => 'required|integer|exists:card_listings,id',
            'sellers.*.items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string|max:255',
            'shipping_address.last_name' => 'required|string|max:255',
            'shipping_address.address_line_1' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.postal_code' => 'required|string|max:10',
            'shipping_address.country' => 'required|string|max:2',
            // CardSwap Shipping V1 - obbligatorio
            'shipping_selections' => 'required|array|min:1',
            'shipping_selections.*.seller_id' => 'required|integer|exists:users,id',
            'shipping_selections.*.shipping_method' => 'required|string',
            'shipping_selections.*.price' => 'required|numeric|min:0',
            'shipping_selections.*.insurance_fee' => 'required|numeric|min:0',
        ];
        
        Log::info('Payment request with CardSwap V1 shipping_selections', [
            'selections_count' => isset($requestData['shipping_selections']) ? count($requestData['shipping_selections']) : 0
        ]);

        $validator = Validator::make($requestData, $validationRules);

        if ($validator->fails()) {
            Log::error('Payment validation failed', [
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
            Log::info('Creating order for payment', [
                'buyer_id' => $orderData['buyer']->id,
                'sellers_count' => count($orderData['sellers']),
                'total_amount' => $orderData['total_amount'],
                'subtotal' => $orderData['subtotal'],
                'shipping_cost' => $orderData['total_shipping_cost'],
                'tax_amount' => $orderData['tax_amount']
            ]);

            $order = $this->createOrder($orderData);
            
            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'seller_payout_amount' => $order->seller_payout_amount,
                'payout_status' => $order->payout_status
            ]);
            
            // Prepara i dati per Stripe
            $stripeData = $this->prepareStripeData($orderData, $order);
            
            Log::info('Preparing Stripe payment', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $stripeData['total_amount'],
                'sellers_count' => count($stripeData['sellers'])
            ]);
            
            // Crea il pagamento con Stripe
            $paymentResult = $this->stripeService->createMultiVendorPayment($stripeData);
            
            Log::info('Stripe payment creation result', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'success' => $paymentResult['success'] ?? false,
                'payment_intent_id' => $paymentResult['payment_intent']->id ?? null
            ]);
            
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

            // Le card listings sono già state aggiornate in createOrder (dentro la transazione)
            // Se il commit va a buon fine, le modifiche sono già applicate
            DB::commit();
            
            Log::info('Transaction committed - card listings should be updated', [
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento creato con successo',
                'payment_intent' => [
                    'id' => $paymentResult['payment_intent']->id,
                    'client_secret' => $paymentResult['client_secret'],
                    'status' => $paymentResult['payment_intent']->status
                ],
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount
                ],
                'order_id' => $order->id // Per compatibilità con codice esistente
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log dell'errore completo per debugging
            Log::error('Errore in createPayment: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            // Verifica se è un errore di database (ENUM non aggiornato, colonne mancanti, ecc.)
            $isDatabaseError = $e instanceof \Illuminate\Database\QueryException || 
                              $e instanceof \PDOException ||
                              str_contains($e->getMessage(), 'SQLSTATE') ||
                              str_contains($e->getMessage(), 'Data truncated') ||
                              str_contains($e->getMessage(), 'Unknown column');
            
            if ($isDatabaseError) {
                Log::critical('Errore database durante creazione ordine - possibile migrazione non applicata', [
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Errore di configurazione del database. Contatta il supporto tecnico.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Errore di configurazione'
                ], 500);
            }
            
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
     * 
     * Usa ESCLUSIVAMENTE CardSwap Shipping V1 (shipping_selections obbligatorio).
     * NON supporta più il sistema legacy basato su shipping_zones.
     */
    private function transformRequestData(array $requestData): array
    {
        // Se i dati sono già nel formato corretto (sellers), verifica se ha shipping_selections
        if (isset($requestData['sellers']) && isset($requestData['shipping_address'])) {
            // Verifica che shipping_selections sia presente
            if (!isset($requestData['shipping_selections']) || empty($requestData['shipping_selections'])) {
                Log::error('PaymentController::transformRequestData - shipping_selections mancante', [
                    'has_shipping_selections' => isset($requestData['shipping_selections']),
                    'sellers_count' => count($requestData['sellers'] ?? [])
                ]);
                return [
                    'sellers' => $requestData['sellers'] ?? [],
                    'shipping_address' => $requestData['shipping_address'] ?? [],
                    '_error' => 'Metodo di spedizione mancante. Ricarica il checkout.'
                ];
            }
            
            // Se ha shipping_selections, aggiungili ai sellers
            $this->attachShippingSelectionsToSellers($requestData);
            return $requestData;
        }

        // Trasforma dal formato frontend (cart_data + address) al formato backend (sellers + shipping_address)
        if (isset($requestData['cart_data']) && isset($requestData['address'])) {
            // Verifica che shipping_selections sia presente (obbligatorio)
            if (!isset($requestData['shipping_selections']) || empty($requestData['shipping_selections'])) {
                Log::error('PaymentController::transformRequestData - shipping_selections mancante per cart_data', [
                    'has_shipping_selections' => isset($requestData['shipping_selections']),
                    'has_cart_data' => isset($requestData['cart_data']),
                    'has_address' => isset($requestData['address'])
                ]);
                return [
                    'sellers' => [],
                    'shipping_address' => [],
                    '_error' => 'Metodo di spedizione mancante. Ricarica il checkout.'
                ];
            }
            
            Log::info('Transforming request data with CardSwap V1 shipping_selections');
            return $this->transformRequestDataWithCardSwapV1($requestData);
        }

        // Se non riusciamo a trasformare, restituisci i dati originali
        // La validazione fallirà e mostrerà l'errore appropriato
        return $requestData;
    }

    /**
     * Trasforma i dati usando CardSwap V1 shipping_selections
     */
    private function transformRequestDataWithCardSwapV1(array $requestData): array
    {
        $cartData = $requestData['cart_data'];
        $shippingSelections = $requestData['shipping_selections'] ?? [];
        $sellers = [];
        
        // Crea una mappa shipping_selections per seller_id
        $selectionsMap = [];
        foreach ($shippingSelections as $selection) {
            $selectionsMap[$selection['seller_id']] = $selection;
        }

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

            // Verifica che esista shipping_selection per questo seller
            if (!isset($selectionsMap[$sellerId])) {
                Log::error('Shipping selection missing for seller in CardSwap V1', [
                    'seller_id' => $sellerId,
                    'available_selections' => array_keys($selectionsMap)
                ]);
                continue;
            }

            $selection = $selectionsMap[$sellerId];
            
            // Valida shipping_method
            if (!ShippingMethod::isValidIncludingVirtual($selection['shipping_method'])) {
                Log::error('Invalid shipping_method in CardSwap V1 selection', [
                    'seller_id' => $sellerId,
                    'shipping_method' => $selection['shipping_method']
                ]);
                continue;
            }

            // Calcola shipping_cost totale (price + insurance_fee)
            $shippingCost = (float) $selection['price'] + (float) ($selection['insurance_fee'] ?? 0);

            $sellers[] = [
                'seller_id' => (int) $sellerId,
                'items' => $sellerItems,
                // CardSwap V1 fields
                'shipping_method' => $selection['shipping_method'],
                'shipping_cost' => $shippingCost,
                'shipping_price' => (float) $selection['price'],
                'insurance_fee' => (float) ($selection['insurance_fee'] ?? 0),
                // Legacy fields (nullable per backward compatibility)
                'shipping_zone_id' => null,
                'selected_shipping_method' => null,
            ];

            Log::info('Seller added with CardSwap V1 shipping', [
                'seller_id' => $sellerId,
                'shipping_method' => $selection['shipping_method'],
                'shipping_cost' => $shippingCost,
                'items_count' => count($sellerItems),
            ]);
        }

        if (empty($sellers)) {
            Log::error('No valid sellers found after CardSwap V1 transformation', [
                'cart_data_keys' => array_keys($cartData),
                'selections_count' => count($shippingSelections)
            ]);
            
            return [
                'sellers' => [],
                'shipping_address' => [],
                '_error' => 'Nessuna selezione di spedizione valida trovata per i venditori selezionati.',
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
            'shipping_selections' => $shippingSelections, // Mantieni per riferimento
        ];
    }

    /**
     * Attacca shipping_selections ai sellers quando i dati sono già nel formato corretto
     */
    private function attachShippingSelectionsToSellers(array &$requestData): void
    {
        if (!isset($requestData['shipping_selections']) || !is_array($requestData['shipping_selections'])) {
            return;
        }

        $selectionsMap = [];
        foreach ($requestData['shipping_selections'] as $selection) {
            $selectionsMap[$selection['seller_id']] = $selection;
        }

        foreach ($requestData['sellers'] as &$sellerData) {
            $sellerId = $sellerData['seller_id'];
            if (isset($selectionsMap[$sellerId])) {
                $selection = $selectionsMap[$sellerId];
                $sellerData['shipping_method'] = $selection['shipping_method'];
                $sellerData['shipping_cost'] = (float) $selection['price'] + (float) ($selection['insurance_fee'] ?? 0);
                $sellerData['shipping_price'] = (float) $selection['price'];
                $sellerData['insurance_fee'] = (float) ($selection['insurance_fee'] ?? 0);
            }
        }
    }

    /**
     * Prepara i dati dell'ordine
     * 
     * Usa ESCLUSIVAMENTE CardSwap Shipping V1 (shipping_selections).
     * NON supporta più il sistema legacy basato su shipping_zones.
     */
    private function prepareOrderData(array $requestData, User $buyer): array
    {
        $sellers = $requestData['sellers'];
        $subtotal = 0;
        $totalShippingCost = 0;
        $orderItems = [];
        
        // Verifica che shipping_selections sia presente
        if (!isset($requestData['shipping_selections']) || empty($requestData['shipping_selections'])) {
            Log::error('PaymentController::prepareOrderData - shipping_selections mancante', [
                'has_shipping_selections' => isset($requestData['shipping_selections']),
                'sellers_count' => count($sellers),
                'buyer_id' => $buyer->id
            ]);
            throw new \Exception('Metodo di spedizione mancante. Ricarica il checkout.');
        }

        Log::info('PaymentController::prepareOrderData - Using CardSwap Shipping V1', [
            'sellers_count' => count($sellers),
            'shipping_selections_count' => count($requestData['shipping_selections']),
            'buyer_id' => $buyer->id
        ]);

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

            // CardSwap V1: usa shipping_cost già calcolato (price + insurance_fee)
            // NON ricalcolare, fidati del risultato del CardSwapShippingController
            if (!isset($sellerData['shipping_cost']) || $sellerData['shipping_cost'] === null) {
                Log::error('CardSwap V1 shipping_cost missing for seller', [
                    'seller_id' => $sellerData['seller_id'],
                    'has_shipping_method' => isset($sellerData['shipping_method']),
                    'has_shipping_price' => isset($sellerData['shipping_price']),
                    'has_insurance_fee' => isset($sellerData['insurance_fee'])
                ]);
                throw new \Exception("Costo di spedizione mancante per venditore {$seller->name}. Ricarica il checkout.");
            }

            $shippingCost = (float) $sellerData['shipping_cost'];
            
            // Calcola package_bucket e logistic_units_total per questo venditore
            $bucketData = $this->calculatePackageBucketForSeller($sellerData['items']);
            $sellerData['package_bucket'] = $bucketData['bucket'];
            $sellerData['logistic_units_total'] = $bucketData['logistic_units_total'];
            
            Log::info('Using CardSwap V1 shipping cost', [
                'seller_id' => $sellerData['seller_id'],
                'shipping_cost' => $shippingCost,
                'shipping_method' => $sellerData['shipping_method'] ?? null,
                'package_bucket' => $sellerData['package_bucket'],
                'logistic_units_total' => $sellerData['logistic_units_total']
            ]);
            
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
            'order_items' => $orderItems,
            'use_cardswap_v1' => true, // Sempre true ora
        ];
    }

    /**
     * Crea l'ordine nel database
     */
    private function createOrder(array $orderData): Order
    {
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT);

        // Calcola l'importo del payout per ogni venditore: 94% del subtotale + spedizione (pagata dall'acquirente al venditore)
        $sellerPayoutAmount = 0;
        foreach ($orderData['sellers'] as $sellerData) {
            $sellerSubtotal = 0;
            foreach ($sellerData['items'] as $itemData) {
                $listing = \App\Models\CardListing::find($itemData['listing_id']);
                if ($listing) {
                    $sellerSubtotal += $listing->price * $itemData['quantity'];
                }
            }
            $sellerShipping = (float) ($sellerData['shipping_cost'] ?? 0);
            $sellerPayoutAmount += $sellerSubtotal * 0.94 + $sellerShipping;
        }

        // Crea l'ordine (senza campi shipping_method, package_bucket, logistic_units_total)
        // Questi dati vengono salvati nella tabella order_shippings (uno per ogni seller)
        $order = Order::create([
            'order_number' => $orderNumber,
            'buyer_id' => $orderData['buyer']->id,
            'seller_id' => $orderData['sellers'][0]['seller_id'], // Primo venditore come principale
            'status' => 'pending',
            'subtotal' => $orderData['subtotal'] ?? ($orderData['total_amount'] - $orderData['total_shipping_cost'] - ($orderData['tax_amount'] ?? 0)),
            'shipping_cost' => $orderData['total_shipping_cost'], // Totale spedizione (somma di tutti i seller)
            'tax_amount' => $orderData['tax_amount'] ?? 0, // Costo di gestione (1.5% sul subtotale)
            'total_amount' => $orderData['total_amount'],
            'shipping_address' => $orderData['shipping_address'],
            'billing_address' => $orderData['shipping_address'], // Per ora uguale
            // Nuovi campi per il sistema di trattenuta fondi
            'seller_payout_amount' => $sellerPayoutAmount,
            'payout_status' => 'pending_payout', // Fondi trattenuti, in attesa di consegna
        ]);

        Log::info('Order created in database', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'buyer_id' => $order->buyer_id,
            'seller_id' => $order->seller_id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'seller_payout_amount' => $order->seller_payout_amount,
            'payout_status' => $order->payout_status,
            'shipping_cost' => $order->shipping_cost,
            'use_cardswap_v1' => $orderData['use_cardswap_v1'] ?? false
        ]);

        // Salva i dati di spedizione CardSwap V1 per OGNI seller nella tabella order_shippings
        if ($orderData['use_cardswap_v1'] ?? false) {
            foreach ($orderData['sellers'] as $sellerData) {
                $sellerId = $sellerData['seller_id'];
                
                // Verifica che i dati CardSwap V1 siano presenti
                if (!isset($sellerData['shipping_method']) || 
                    !isset($sellerData['package_bucket']) || 
                    !isset($sellerData['logistic_units_total'])) {
                    Log::warning('CardSwap V1 shipping data missing for seller', [
                        'order_id' => $order->id,
                        'seller_id' => $sellerId,
                        'has_shipping_method' => isset($sellerData['shipping_method']),
                        'has_package_bucket' => isset($sellerData['package_bucket']),
                        'has_logistic_units_total' => isset($sellerData['logistic_units_total'])
                    ]);
                    continue;
                }

                OrderShipping::create([
                    'order_id' => $order->id,
                    'seller_id' => $sellerId,
                    'shipping_method' => $sellerData['shipping_method'],
                    'package_bucket' => $sellerData['package_bucket'],
                    'logistic_units_total' => $sellerData['logistic_units_total'],
                    'shipping_price' => $sellerData['shipping_price'] ?? null,
                    'insurance_fee' => $sellerData['insurance_fee'] ?? 0.00,
                ]);

                Log::info('OrderShipping created for seller', [
                    'order_id' => $order->id,
                    'seller_id' => $sellerId,
                    'shipping_method' => $sellerData['shipping_method'],
                    'package_bucket' => $sellerData['package_bucket'],
                    'logistic_units_total' => $sellerData['logistic_units_total'],
                    'shipping_price' => $sellerData['shipping_price'] ?? null,
                    'insurance_fee' => $sellerData['insurance_fee'] ?? 0.00,
                ]);
            }
        }

        // Crea gli OrderItem e aggiorna disponibilità card listings
        foreach ($orderData['order_items'] as $itemData) {
            OrderItem::create([
                'order_id' => $order->id,
                'card_listing_id' => $itemData['card_listing_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total_price' => $itemData['total_price'],
                'condition' => $itemData['condition']
            ]);

            // Aggiorna la disponibilità della card listing
            $listing = \App\Models\CardListing::find($itemData['card_listing_id']);
            if ($listing) {
                $originalQuantity = $listing->quantity;
                $quantityToSell = $itemData['quantity'];
                
                // Decrementa la quantità disponibile
                $listing->decrement('quantity', $quantityToSell);
                
                // Ricarica per avere la quantità aggiornata
                $listing->refresh();
                
                // Se la carta è esaurita, marcala come venduta
                if ($listing->quantity <= 0) {
                    $listing->markAsSold();
                    
                    Log::info('Card listing marked as sold', [
                        'listing_id' => $listing->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'quantity_sold' => $quantityToSell,
                        'original_quantity' => $originalQuantity,
                        'new_quantity' => $listing->quantity,
                        'new_status' => $listing->status
                    ]);
                    
                    // Trigger evento per notifiche
                    event(new \App\Events\ListingSold($listing, $order));
                } else {
                    Log::info('Card listing quantity decremented', [
                        'listing_id' => $listing->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'quantity_sold' => $quantityToSell,
                        'original_quantity' => $originalQuantity,
                        'remaining_quantity' => $listing->quantity,
                        'status' => $listing->status
                    ]);
                }
            } else {
                Log::warning('Card listing not found when creating order item', [
                    'card_listing_id' => $itemData['card_listing_id'],
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
            }
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

            // NOTA: Shippo è DEPRECATO - CardSwap V1 NON usa Shippo per spedizione
            // La spedizione viene gestita tramite CardSwap Shipping V1 (shipping_price_tables)
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
        // NOTA: Shippo è DEPRECATO - CardSwap V1 NON usa Shippo
        // Il netto per CardSwap sarà: 6% + 1,5% + spedizione - costi Stripe

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

        // Verifica che shipping_selections sia presente (CardSwap V1 obbligatorio)
        if (!isset($requestData['shipping_selections']) || empty($requestData['shipping_selections'])) {
            Log::error('PaymentController::validateOrderData - shipping_selections mancante', [
                'has_shipping_selections' => isset($requestData['shipping_selections']),
                'sellers_count' => count($sellers),
                'buyer_id' => $buyer->id
            ]);
            $errors['shipping'][] = 'Metodo di spedizione mancante. Ricarica il checkout.';
            return [
                'valid' => false,
                'errors' => $errors
            ];
        }

        // Validazione CardSwap V1
        $shippingSelections = $requestData['shipping_selections'];
        $sellerIdsInOrder = array_column($sellers, 'seller_id');
        
        // Verifica che ogni seller abbia una shipping_selection e coerenza metodo/bucket/subtotale
        foreach ($sellers as $sellerData) {
            $sellerId = $sellerData['seller_id'];
            $selection = collect($shippingSelections)->firstWhere('seller_id', $sellerId);
            
            if (!$selection) {
                $errors['shipping'][] = "Selezione di spedizione mancante per venditore {$sellerId}";
                continue;
            }
            
            // Valida shipping_method
            if (!ShippingMethod::isValidIncludingVirtual($selection['shipping_method'])) {
                $errors['shipping'][] = "Metodo di spedizione non valido per venditore {$sellerId}: {$selection['shipping_method']}";
            }
            
            // Verifica che seller_id corrisponda
            if ($selection['seller_id'] != $sellerId) {
                $errors['shipping'][] = "Seller ID non corrispondente nella selezione spedizione per venditore {$sellerId}";
            }

            // AUDIT-FIX: validazione coerenza shipping_method vs package_bucket e subtotale (specifica V1)
            $bucketData = $this->calculatePackageBucketForSeller($sellerData['items']);
            $sellerSubtotal = 0;
            foreach ($sellerData['items'] as $itemData) {
                $listing = CardListing::find($itemData['listing_id']);
                if ($listing) {
                    $sellerSubtotal += $listing->price * (int) $itemData['quantity'];
                }
            }
            $methodBucketError = $this->validateShippingMethodForBucketAndSubtotal(
                $selection['shipping_method'],
                $bucketData['bucket'],
                $sellerSubtotal,
                $sellerId
            );
            if ($methodBucketError !== null) {
                $errors['shipping'][] = $methodBucketError;
            }
        }
        
        // Verifica che non ci siano shipping_selections per seller non presenti nell'ordine
        foreach ($shippingSelections as $selection) {
            if (!in_array($selection['seller_id'], $sellerIdsInOrder)) {
                $errors['shipping'][] = "Selezione di spedizione per venditore {$selection['seller_id']} non presente nell'ordine";
            }
        }

        // Verifica venditori e zone di spedizione (legacy o validazioni comuni)
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
                    Log::warning("Errore nell'aggiornamento stato Stripe per venditore {$seller->id}: " . $e->getMessage());
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

            // NOTA: Validazione shipping_zones rimossa - ora usiamo solo CardSwap Shipping V1

            // Verifica prezzi e calcoli (comune a entrambi i sistemi)
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
     * 
     * Usa ESCLUSIVAMENTE CardSwap Shipping V1 (shipping_cost dai sellers).
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

            // CardSwap V1: usa shipping_cost dai sellers (già calcolato)
            $shippingCost = $sellerData['shipping_cost'] ?? 0;
            $totalShippingCost += $shippingCost;

            $sellerTotal = $sellerSubtotal + $shippingCost;
            $totalAmount += $sellerTotal;

            $sellers[] = [
                'seller_id' => $seller->id,
                'seller_name' => $seller->name,
                'items' => $items,
                'subtotal' => $sellerSubtotal,
                'shipping_cost' => $shippingCost,
                'shipping_method' => $sellerData['shipping_method'] ?? null,
                'package_bucket' => $sellerData['package_bucket'] ?? null,
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

    /**
     * Valida coerenza shipping_method con package_bucket e subtotale (specifica CardSwap V1).
     * UNTRACKED solo LETTER + subtotale <= soglia; INSURED solo se subtotale >= 200€.
     *
     * @param string $shippingMethod Metodo selezionato
     * @param string $bucket Bucket calcolato dagli items
     * @param float $sellerSubtotal Subtotale venditore
     * @param int $sellerId Per messaggi di errore
     * @return string|null Messaggio di errore o null se valido
     */
    private function validateShippingMethodForBucketAndSubtotal(string $shippingMethod, string $bucket, float $sellerSubtotal, int $sellerId): ?string
    {
        $maxUntracked = (float) config('shipping.untracked_max_subtotal_eur', 20.00);
        $minInsured = (float) config('shipping.insured_min_subtotal_eur', 200.00);

        if ($shippingMethod === ShippingMethod::UNTRACKED_STANDARD) {
            if ($bucket !== ShippingPackageBucket::LETTER) {
                return "Metodo non tracciato consentito solo per spedizione in lettera (venditore {$sellerId}). Ricarica il checkout.";
            }
            if ($sellerSubtotal > $maxUntracked) {
                return "Spedizione non tracciata consentita solo per ordini fino a " . number_format($maxUntracked, 0, ',', '') . "€ (venditore {$sellerId}). Ricarica il checkout.";
            }
        }

        if ($shippingMethod === ShippingMethod::TRACKED_INSURED) {
            if ($sellerSubtotal < $minInsured) {
                return "Assicurazione obbligatoria per ordini da " . number_format($minInsured, 0, ',', '') . "€; metodo non applicabile (venditore {$sellerId}). Ricarica il checkout.";
            }
        }

        return null;
    }

    /**
     * Calcola package_bucket e logistic_units_total per un venditore
     * 
     * Usa la stessa logica di CardSwapShippingController::calculatePackageBucket()
     * 
     * @param array $items Array di items con listing_id e quantity
     * @return array{ bucket: string, logistic_units_total: float }
     */
    private function calculatePackageBucketForSeller(array $items): array
    {
        $singleCardQty = 0;
        $packQty = 0;
        $boxQty = 0;

        foreach ($items as $itemData) {
            $listing = CardListing::find($itemData['listing_id']);
            if (!$listing) {
                continue;
            }

            // Mappa listing_type a category_type (stessa logica di CardSwapShippingController)
            $categoryType = $this->mapListingTypeToCategoryType($listing->listing_type ?? 'single');
            $quantity = (int) $itemData['quantity'];

            switch ($categoryType) {
                case 'SINGLE_CARD':
                    $singleCardQty += $quantity;
                    break;
                case 'PACK':
                    $packQty += $quantity;
                    break;
                case 'BOX':
                    $boxQty += $quantity;
                    break;
            }
        }

        // Pesi unitari secondo specifica CardSwap V1
        $singleCardWeight = 0.2;
        $packWeight = 0.5;
        $boxWeight = 2.0;

        // Verifica se LETTER è consentita
        $isOnlySingleCards = ($packQty === 0 && $boxQty === 0);
        $canUseLetter = $isOnlySingleCards && $singleCardQty <= 5;

        if ($canUseLetter) {
            return [
                'bucket' => ShippingPackageBucket::LETTER,
                'logistic_units_total' => $singleCardQty * $singleCardWeight,
            ];
        }

        // Calcola total_units per determinare bucket
        $totalUnits = ($singleCardQty * $singleCardWeight) + 
                      ($packQty * $packWeight) + 
                      ($boxQty * $boxWeight);

        // Determina bucket basato su total_units
        if ($totalUnits <= 1.0) {
            $bucket = ShippingPackageBucket::PARCEL_S;
        } elseif ($totalUnits <= 4.0) {
            $bucket = ShippingPackageBucket::PARCEL_M;
        } else {
            $bucket = ShippingPackageBucket::PARCEL_L;
        }

        return [
            'bucket' => $bucket,
            'logistic_units_total' => $totalUnits,
        ];
    }

    /**
     * Mappa listing_type a category_type (stessa logica di CardSwapShippingController)
     */
    private function mapListingTypeToCategoryType(string $listingType): string
    {
        return match ($listingType) {
            'single' => 'SINGLE_CARD',
            'sealed-pack' => 'PACK',
            'sealed-box' => 'BOX',
            'bulk' => 'SINGLE_CARD',
            'lot' => 'SINGLE_CARD',
            default => 'SINGLE_CARD',
        };
    }
}
