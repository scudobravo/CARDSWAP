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
            DB::beginTransaction();

            $buyer = Auth::user();
            
            // Validazione completa prima del pagamento
            $validationResult = $this->validateOrderData($request->all(), $buyer);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore di validazione ordine',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            $orderData = $this->prepareOrderData($request->all(), $buyer);
            
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
            
            return response()->json([
                'success' => false,
                'message' => 'Errore interno del server',
                'error' => $e->getMessage()
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
     * Prepara i dati dell'ordine
     */
    private function prepareOrderData(array $requestData, User $buyer): array
    {
        $sellers = $requestData['sellers'];
        $totalAmount = 0;
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

            $totalAmount += $sellerSubtotal + $shippingCost;
        }

        return [
            'buyer' => $buyer,
            'sellers' => $sellers,
            'total_amount' => $totalAmount,
            'total_shipping_cost' => $totalShippingCost,
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
            'subtotal' => $orderData['total_amount'] - $orderData['total_shipping_cost'],
            'shipping_cost' => $orderData['total_shipping_cost'],
            'tax_amount' => 0, // Per ora 0
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
        $applicationFee = $this->stripeService->calculateApplicationFee($orderData['total_amount']);
        $sellers = [];

        foreach ($orderData['sellers'] as $sellerData) {
            $seller = User::find($sellerData['seller_id']);
            
            if (!$seller->stripe_account_id) {
                throw new \Exception("Venditore {$seller->name} non ha un account Stripe configurato");
            }

            $sellerAmount = 0;
            foreach ($sellerData['items'] as $itemData) {
                $listing = \App\Models\CardListing::find($itemData['listing_id']);
                $sellerAmount += $listing->price * $itemData['quantity'];
            }

            // Aggiungi costo spedizione
            $shippingCost = $listing->getShippingCostForZone($sellerData['shipping_zone_id']);
            $sellerAmount += $shippingCost;

            $sellers[] = [
                'seller_id' => $seller->id,
                'stripe_account_id' => $seller->stripe_account_id,
                'amount' => $sellerAmount
            ];
        }

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

            // Verifica se il venditore può ricevere pagamenti
            if (!$seller->canSellWithStripe()) {
                $errors['sellers'][] = "Venditore {$seller->name} non può ricevere pagamenti";
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
