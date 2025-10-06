<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserAddress;
use App\Models\CardListing;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Crea un nuovo ordine e processa il pagamento
     */
    public function createOrder(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'address' => 'required|array',
                'address.first_name' => 'required|string|max:255',
                'address.last_name' => 'required|string|max:255',
                'address.address_line_1' => 'required|string|max:255',
                'address.city' => 'required|string|max:255',
                'address.country' => 'required|string|max:2',
                'address.postal_code' => 'required|string|max:10',
                'shipping_methods' => 'required|array',
                'payment_method' => 'required|string|in:credit-card,paypal,stripe',
                'cart_data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dati di validazione non validi',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $cartData = $request->input('cart_data');
            $addressData = $request->input('address');
            $shippingMethods = $request->input('shipping_methods');

            // Valida i metodi di spedizione
            $shippingValidation = $this->validateShippingMethods($cartData, $shippingMethods);
            if (!$shippingValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Metodi di spedizione non validi',
                    'errors' => $shippingValidation['errors']
                ], 400);
            }

            // Valida il carrello con controllo disponibilità in tempo reale
            $cartValidation = $this->validateCartWithAvailability($cartData);
            if (!$cartValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrello non valido',
                    'errors' => $cartValidation['errors']
                ], 400);
            }

            // Prenota temporaneamente le quantità (15 minuti)
            $reservationResult = $this->reserveQuantities($cartData);
            if (!$reservationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore nella prenotazione delle quantità',
                    'errors' => $reservationResult['errors']
                ], 400);
            }

            // Calcola i totali
            $totals = $this->calculateOrderTotals($cartData, $shippingMethods);

            DB::beginTransaction();

            try {
                // Salva l'indirizzo se necessario
                $shippingAddress = $this->saveShippingAddress($user, $addressData);

                // Crea gli ordini per ogni venditore
                $orders = [];
                $paymentData = [];

                foreach ($cartData as $sellerId => $items) {
                    $sellerShippingMethod = $shippingMethods[$sellerId] ?? 'standard';
                    $sellerTotal = $this->calculateSellerTotal($items, $sellerShippingMethod);
                    
                    $order = Order::create([
                        'order_number' => $this->generateOrderNumber(),
                        'buyer_id' => $user->id,
                        'seller_id' => $sellerId,
                        'status' => 'pending',
                        'subtotal' => $sellerTotal['subtotal'],
                        'shipping_cost' => $sellerTotal['shipping'],
                        'tax_amount' => $sellerTotal['tax'],
                        'total_amount' => $sellerTotal['total'],
                        'shipping_address' => $shippingAddress,
                        'billing_address' => $shippingAddress, // Per ora uguale alla spedizione
                        'notes' => 'Ordine creato tramite checkout'
                    ]);

                    // Crea gli articoli dell'ordine
                    foreach ($items as $item) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'card_listing_id' => $item['id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total_price' => $item['unit_price'] * $item['quantity'],
                            'condition' => $item['condition']
                        ]);

                        // Aggiorna la disponibilità
                        $listing = CardListing::find($item['id']);
                        if ($listing) {
                            $listing->decrement('quantity', $item['quantity']);
                            
                            // Se la carta è esaurita, marcala come venduta
                            if ($listing->quantity <= 0) {
                                $listing->markAsSold();
                                
                                // Trigger evento per notifiche
                                event(new \App\Events\ListingSold($listing, $order));
                            }
                        }
                    }

                    $orders[] = $order;
                    $paymentData['sellers'][] = [
                        'seller_id' => $sellerId,
                        'amount' => $sellerTotal['total'],
                        'stripe_account_id' => $this->getSellerStripeAccount($sellerId)
                    ];
                }

                // Processa il pagamento
                $paymentResult = $this->processPayment($paymentData, $request->input('payment_method'));

                if ($paymentResult['success']) {
                    // Aggiorna gli ordini con il pagamento
                    foreach ($orders as $order) {
                        $order->update([
                            'stripe_payment_intent_id' => $paymentResult['payment_intent_id'] ?? null,
                            'paid_at' => now(),
                            'status' => 'paid'
                        ]);
                    }

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Ordine creato con successo',
                        'order_id' => $orders[0]->id, // ID del primo ordine
                        'payment_intent' => $paymentResult['payment_intent'] ?? null,
                        'orders' => $orders
                    ]);
                } else {
                    throw new \Exception($paymentResult['error'] ?? 'Errore nel pagamento');
                }

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione dell\'ordine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida i dati del carrello con controllo disponibilità in tempo reale
     */
    private function validateCartWithAvailability(array $cartData): array
    {
        $errors = [];

        foreach ($cartData as $sellerId => $items) {
            foreach ($items as $item) {
                $listing = CardListing::with(['cardModel', 'seller'])->find($item['id']);
                
                if (!$listing) {
                    $errors[] = "Inserzione {$item['id']} non trovata";
                    continue;
                }

                if ($listing->status !== 'active') {
                    $errors[] = "Inserzione {$item['id']} non più disponibile";
                    continue;
                }

                // Controllo disponibilità in tempo reale
                $availableQuantity = $this->getAvailableQuantity($item['id']);
                if ($availableQuantity < $item['quantity']) {
                    $errors[] = "Solo {$availableQuantity} unità disponibili per l'inserzione {$item['id']}";
                    continue;
                }

                // Controllo prezzo con tolleranza minima
                if (abs($listing->price - $item['unit_price']) > 0.01) {
                    $errors[] = "Prezzo aggiorato per l'inserzione {$item['id']}: €{$listing->price} (era €{$item['unit_price']})";
                    continue;
                }

                // Controllo venditore
                if ($listing->seller_id != $sellerId) {
                    $errors[] = "Venditore non corrispondente per l'inserzione {$item['id']}";
                    continue;
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Ottiene la quantità disponibile per un'inserzione (considerando prenotazioni)
     */
    private function getAvailableQuantity(int $listingId): int
    {
        $listing = CardListing::find($listingId);
        if (!$listing) return 0;

        // TODO: Implementare controllo prenotazioni attive
        // Per ora restituisce la quantità totale
        return $listing->quantity;
    }

    /**
     * Prenota temporaneamente le quantità
     */
    private function reserveQuantities(array $cartData): array
    {
        $errors = [];
        $reservations = [];

        try {
            foreach ($cartData as $sellerId => $items) {
                foreach ($items as $item) {
                    $availableQuantity = $this->getAvailableQuantity($item['id']);
                    if ($availableQuantity < $item['quantity']) {
                        $errors[] = "Quantità non disponibile per l'inserzione {$item['id']}";
                        continue;
                    }

                    // TODO: Implementare sistema di prenotazione
                    // Per ora simuliamo la prenotazione
                    $reservations[] = [
                        'listing_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'expires_at' => now()->addMinutes(15)
                    ];
                }
            }

            return [
                'success' => empty($errors),
                'errors' => $errors,
                'reservations' => $reservations
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Errore nella prenotazione: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Valida i metodi di spedizione per ogni venditore
     */
    private function validateShippingMethods(array $cartData, array $shippingMethods): array
    {
        $errors = [];

        foreach ($cartData as $sellerId => $items) {
            if (!isset($shippingMethods[$sellerId])) {
                $errors[] = "Metodo di spedizione non selezionato per il venditore {$sellerId}";
                continue;
            }

            $method = $shippingMethods[$sellerId];
            if (!in_array($method, ['standard', 'express'])) {
                $errors[] = "Metodo di spedizione non valido per il venditore {$sellerId}: {$method}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Calcola i totali dell'ordine
     */
    private function calculateOrderTotals(array $cartData, array $shippingMethods): array
    {
        $subtotal = 0;
        $shipping = 0;
        $tax = 0;

        foreach ($cartData as $sellerId => $items) {
            foreach ($items as $item) {
                $subtotal += $item['unit_price'] * $item['quantity'];
            }
            
            // Calcola spedizione per venditore usando il metodo selezionato
            $sellerShippingMethod = $shippingMethods[$sellerId] ?? 'standard';
            $shipping += $this->calculateShippingCost($sellerShippingMethod);
        }

        $tax = $subtotal * 0.22; // 22% IVA
        $total = $subtotal + $shipping + $tax;

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total
        ];
    }

    /**
     * Calcola i totali per un singolo venditore
     */
    private function calculateSellerTotal(array $items, string $deliveryMethod): array
    {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'];
        }

        $shipping = $this->calculateShippingCost($deliveryMethod);
        $tax = $subtotal * 0.22; // 22% IVA
        $total = $subtotal + $shipping + $tax;

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total
        ];
    }

    /**
     * Calcola il costo di spedizione
     */
    private function calculateShippingCost(string $deliveryMethod): float
    {
        return match ($deliveryMethod) {
            'standard' => 5.00,
            'express' => 16.00,
            default => 5.00
        };
    }

    /**
     * Salva l'indirizzo di spedizione
     */
    private function saveShippingAddress($user, array $addressData): array
    {
        // Cerca un indirizzo esistente
        $existingAddress = UserAddress::where('user_id', $user->id)
            ->where('first_name', $addressData['first_name'])
            ->where('last_name', $addressData['last_name'])
            ->where('address_line_1', $addressData['address_line_1'])
            ->where('city', $addressData['city'])
            ->where('postal_code', $addressData['postal_code'])
            ->first();

        if (!$existingAddress) {
            $existingAddress = UserAddress::create([
                'user_id' => $user->id,
                'label' => $addressData['first_name'] . ' ' . $addressData['last_name'],
                'first_name' => $addressData['first_name'],
                'last_name' => $addressData['last_name'],
                'company' => $addressData['company'] ?? null,
                'address_line_1' => $addressData['address_line_1'],
                'address_line_2' => $addressData['address_line_2'] ?? null,
                'city' => $addressData['city'],
                'state_province' => $addressData['state_province'] ?? null,
                'postal_code' => $addressData['postal_code'],
                'country' => $addressData['country'],
                'phone' => $addressData['phone'] ?? null,
                'is_default' => false,
                'is_billing' => false,
                'is_shipping' => true
            ]);
        }

        return $existingAddress->toArray();
    }

    /**
     * Processa il pagamento
     */
    private function processPayment(array $paymentData, string $paymentMethod): array
    {
        switch ($paymentMethod) {
            case 'stripe':
            case 'credit-card':
                return $this->processStripePayment($paymentData);
            case 'paypal':
                return $this->processPayPalPayment($paymentData);
            default:
                return [
                    'success' => false,
                    'error' => 'Metodo di pagamento non supportato'
                ];
        }
    }

    /**
     * Processa pagamento Stripe
     */
    private function processStripePayment(array $paymentData): array
    {
        try {
            $totalAmount = array_sum(array_column($paymentData['sellers'], 'amount'));
            $applicationFee = $this->stripeService->calculateApplicationFee($totalAmount);

            $stripeData = [
                'amount' => $totalAmount,
                'currency' => 'eur',
                'application_fee' => $applicationFee,
                'metadata' => [
                    'type' => 'multi_vendor_order'
                ]
            ];

            if (count($paymentData['sellers']) === 1) {
                // Pagamento singolo venditore
                $stripeData['seller_account_id'] = $paymentData['sellers'][0]['stripe_account_id'];
                $result = $this->stripeService->createSingleVendorPayment($stripeData);
            } else {
                // Pagamento multi-venditore
                $stripeData['sellers'] = $paymentData['sellers'];
                $result = $this->stripeService->createMultiVendorPayment($stripeData);
            }

            return [
                'success' => $result['success'],
                'payment_intent' => $result['payment_intent'] ?? null,
                'payment_intent_id' => $result['payment_intent']->id ?? null,
                'error' => $result['error'] ?? null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processa pagamento PayPal (placeholder)
     */
    private function processPayPalPayment(array $paymentData): array
    {
        // Implementare integrazione PayPal
        return [
            'success' => false,
            'error' => 'PayPal non ancora implementato'
        ];
    }

    /**
     * Ottiene l'account Stripe del venditore
     */
    private function getSellerStripeAccount(string $sellerId): string
    {
        $seller = \App\Models\User::find($sellerId);
        return $seller->stripe_account_id ?? '';
    }

    /**
     * Genera un numero d'ordine unico
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'CS-' . strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Ottieni lo stato di un ordine
     */
    public function getOrderStatus(Request $request): JsonResponse
    {
        try {
            $orderId = $request->input('order_id');
            $order = Order::find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ordine non trovato'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'order' => $order,
                'status' => $order->status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dello stato dell\'ordine',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
