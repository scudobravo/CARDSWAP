<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\ShippingMethod;
use App\Helpers\ShippingAuditLog;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AfterShipService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API ordini per il venditore – CardSwap Shipping V1 Post-Order (FASE D1).
 * GET/POST /api/seller/orders/:orderId e azioni tracking / mark-shipped.
 */
class SellerOrderController extends Controller
{
    /**
     * Dettaglio ordine per il venditore (solo dati relativi a questo seller).
     * GET /api/seller/orders/:orderId
     */
    public function show(string $orderId): JsonResponse
    {
        try {
            $user = Auth::user();
            $order = $this->findOrderForSeller($orderId, $user->id);

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Ordine non trovato'], 404);
            }

            $order->load([
                'orderItems' => function ($q) use ($user) {
                    $q->whereHas('cardListing', fn ($q2) => $q2->where('seller_id', $user->id))
                        ->with('cardListing.cardModel');
                },
                'buyer',
                'orderShippings' => fn ($q) => $q->where('seller_id', $user->id),
            ]);

            $orderShipping = $order->orderShippings->first();
            $shipmentStatus = $this->deriveShipmentStatus($order);

            // Riepilogo spedizione: usa order_shippings se presente, altrimenti fallback con prezzo da order (ordini legacy)
            $orderShippingPayload = null;
            if ($orderShipping) {
                $orderShippingPayload = [
                    'id' => $orderShipping->id,
                    'shipping_method' => $orderShipping->shipping_method,
                    'package_bucket' => $orderShipping->package_bucket,
                    'logistic_units_total' => (float) $orderShipping->logistic_units_total,
                    'shipping_price' => (float) ($orderShipping->shipping_price ?? $order->shipping_cost),
                    'insurance_fee' => (float) $orderShipping->insurance_fee,
                    'insurance_included' => (float) $orderShipping->insurance_fee > 0,
                ];
            } elseif ($order->shipping_cost > 0 || $order->shipping_cost !== null) {
                // Ordini legacy senza order_shippings: metodo null, la UI distingue per prezzo (soglia €6)
                $orderShippingPayload = [
                    'id' => null,
                    'shipping_method' => null,
                    'package_bucket' => null,
                    'logistic_units_total' => null,
                    'shipping_price' => (float) $order->shipping_cost,
                    'insurance_fee' => 0.0,
                    'insurance_included' => false,
                ];
            }

            $response = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'shipment_status' => $shipmentStatus,
                'paid_at' => $order->paid_at?->toIso8601String(),
                'shipping_address' => $order->shipping_address,
                'shipping_country_code' => $order->shipping_address['country'] ?? null,
                'subtotal_eur' => (float) $order->subtotal,
                'shipping_cost' => (float) $order->shipping_cost,
                'total_amount' => (float) $order->total_amount,
                'tracking_number' => $order->tracking_number,
                'carrier_code' => $order->carrier_code,
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'has_dispute' => (bool) $order->has_dispute,
                'payout_status' => $order->payout_status,
                'order_items' => $order->orderItems->map(fn ($item) => [
                    'id' => $item->id,
                    'card_listing_id' => $item->card_listing_id,
                    'card_model' => $item->cardListing?->cardModel ? [
                        'id' => $item->cardListing->cardModel->id,
                        'name' => $item->cardListing->cardModel->name,
                        'set_name' => $item->cardListing->cardModel->set_name ?? null,
                    ] : null,
                    'condition' => $item->condition,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'total_price' => (float) $item->total_price,
                ]),
                'order_shipping' => $orderShippingPayload,
                'buyer' => $order->buyer ? [
                    'id' => $order->buyer->id,
                    'name' => $order->buyer->name,
                ] : null,
                'tracking_deadline_at' => $this->trackingDeadlineAt($order),
            ];

            return response()->json(['success' => true, 'data' => $response]);
        } catch (\Exception $e) {
            Log::error('SellerOrderController show', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Errore nel recupero dell\'ordine'], 500);
        }
    }

    /**
     * Inserisci tracking (spedizione tracciata).
     * POST /api/seller/orders/:orderId/tracking
     * D5: state guard (solo PAID_WAITING_SHIPMENT), idempotenza (tracking già inserito → 409).
     */
    public function addTracking(Request $request, string $orderId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tracking_number' => 'required|string|max:255',
            'carrier_slug' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Dati non validi', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $order = $this->findOrderForSeller($orderId, $user->id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Ordine non trovato'], 404);
        }

        // D5: idempotenza – tracking già inserito → non sovrascrivere
        if (!empty($order->tracking_number)) {
            Log::warning('D5: addTracking rifiutato – tracking già inserito', [
                'order_id' => $order->id,
                'seller_id' => $user->id,
                'current_tracking' => $order->tracking_number,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Tracking già inserito per questo ordine.',
            ], 409);
        }

        // D5: state guard – solo stati “in attesa spedizione”
        $allowedStatuses = ['paid_funds_held', 'paid', 'confirmed', 'pending_payment', 'pending'];
        if (!in_array($order->status, $allowedStatuses, true)) {
            Log::warning('D5: addTracking rifiutato – stato ordine non valido', [
                'order_id' => $order->id,
                'seller_id' => $user->id,
                'current_status' => $order->status,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Inserimento tracking consentito solo per ordini in attesa di spedizione.',
                'current_status' => $order->status,
            ], 409);
        }

        // D5: vietato se ordine in disputa / annullato / rilasciato
        if ($order->has_dispute || in_array($order->status, ['cancelled', 'refunded', 'dispute_hold', 'completed'], true)) {
            Log::warning('D5: addTracking rifiutato – ordine non modificabile', [
                'order_id' => $order->id,
                'seller_id' => $user->id,
                'status' => $order->status,
                'has_dispute' => $order->has_dispute,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Non è possibile inserire il tracking per questo ordine.',
            ], 409);
        }

        // Verifica il tracking con AfterShip prima di salvare: se KO non salviamo e restituiamo errore
        $trackingNumber = $request->input('tracking_number');
        $carrierSlug = $request->input('carrier_slug');
        $createResult = app(AfterShipService::class)->createTracking($order, $trackingNumber, $carrierSlug);

        if (!($createResult['success'] ?? false)) {
            $message = $createResult['message'] ?? 'Il numero di tracking non è stato accettato. Controlla il codice e il corriere e riprova.';
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        $order->update([
            'tracking_number' => $trackingNumber,
            'carrier_code' => $carrierSlug,
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        event(new \App\Events\TrackingAdded($order->fresh(['seller', 'buyer'])));

        ShippingAuditLog::log(
            ShippingAuditLog::ACTION_TRACKING_INSERTED,
            ShippingAuditLog::SOURCE_API,
            (int) $order->id,
            (int) $order->seller_id,
            (int) $order->buyer_id,
            ['tracking_number' => $order->tracking_number]
        );

        return response()->json([
            'success' => true,
            'message' => 'Tracking inserito correttamente',
            'data' => [
                'tracking_number' => $order->tracking_number,
                'carrier_code' => $order->carrier_code,
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'status' => $order->status,
            ],
        ]);
    }

    /**
     * Modifica numero di tracking (ordine già spedito con tracking).
     * PATCH /api/seller/orders/:orderId/tracking
     * Verifica con AfterShip prima di salvare; solo ordini in stato shipped/in_transit/delivered_pending_72h.
     */
    public function updateTracking(Request $request, string $orderId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tracking_number' => 'required|string|max:255',
                'carrier_slug' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Dati non validi', 'errors' => $validator->errors()], 422);
            }

            $user = Auth::user();
            $order = $this->findOrderForSeller($orderId, $user->id);
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Ordine non trovato'], 404);
            }

            if (empty($order->tracking_number)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questo ordine non ha ancora un tracking. Usa "Inserisci tracking" per aggiungerlo.',
                ], 409);
            }

            $allowedStatuses = ['shipped', 'in_transit_verified', 'label_created', 'delivered_pending_72h'];
            if (!in_array($order->status, $allowedStatuses, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non è possibile modificare il tracking per ordini completati, annullati o in disputa.',
                    'current_status' => $order->status,
                ], 409);
            }

            if ($order->has_dispute) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non è possibile modificare il tracking con una disputa aperta.',
                ], 409);
            }

            $newTrackingNumber = is_string($request->input('tracking_number')) ? trim($request->input('tracking_number')) : '';
            $newCarrierSlug = $request->input('carrier_slug');
            if (is_string($newCarrierSlug)) {
                $newCarrierSlug = trim($newCarrierSlug) ?: null;
            } else {
                $newCarrierSlug = null;
            }

            if ($newTrackingNumber === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Inserisci il numero di tracking.',
                ], 422);
            }

            if ($newTrackingNumber === $order->tracking_number && ($newCarrierSlug ?? '') === ($order->carrier_code ?? '')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nessuna modifica',
                    'data' => [
                        'tracking_number' => $order->tracking_number,
                        'carrier_code' => $order->carrier_code,
                        'shipped_at' => $order->shipped_at?->toIso8601String(),
                        'status' => $order->status,
                    ],
                ]);
            }

            $createResult = app(AfterShipService::class)->createTracking($order, $newTrackingNumber, $newCarrierSlug ?: null);
            if (!($createResult['success'] ?? false)) {
                $message = $createResult['message'] ?? 'Il numero di tracking non è stato accettato. Controlla il codice e il corriere e riprova.';
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            $oldTracking = $order->tracking_number;
            $order->update([
                'tracking_number' => $newTrackingNumber,
                'carrier_code' => $newCarrierSlug ?? $order->carrier_code,
            ]);

            ShippingAuditLog::log(
                ShippingAuditLog::ACTION_TRACKING_UPDATED,
                ShippingAuditLog::SOURCE_API,
                (int) $order->id,
                (int) $order->seller_id,
                (int) $order->buyer_id,
                ['old_tracking_number' => $oldTracking, 'tracking_number' => $order->tracking_number]
            );

            return response()->json([
                'success' => true,
                'message' => 'Tracking aggiornato correttamente',
                'data' => [
                    'tracking_number' => $order->tracking_number,
                    'carrier_code' => $order->carrier_code,
                    'shipped_at' => $order->shipped_at?->toIso8601String(),
                    'status' => $order->status,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('updateTracking exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento del tracking. Riprova o contatta l\'assistenza.',
            ], 500);
        }
    }

    /**
     * Segna come spedito (spedizione non tracciata).
     * POST /api/seller/orders/:orderId/mark-shipped
     * D5: solo se metodo = UNTRACKED_STANDARD; state guard; idempotenza (già spedito → 409).
     */
    public function markShipped(Request $request, string $orderId): JsonResponse
    {
        $user = Auth::user();
        $order = $this->findOrderForSeller($orderId, $user->id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Ordine non trovato'], 404);
        }

        // D5: idempotenza – shipped già marcato → bloccare
        if ($order->shipped_at !== null) {
            Log::warning('D5: markShipped rifiutato – spedizione già registrata', [
                'order_id' => $order->id,
                'seller_id' => $user->id,
                'shipped_at' => $order->shipped_at?->toIso8601String(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Spedizione già registrata per questo ordine.',
            ], 409);
        }

        // D5: se ha già tracking, l’ordine è tracciato → deve usare addTracking
        if (!empty($order->tracking_number)) {
            Log::warning('D5: markShipped rifiutato – ordine con tracking', [
                'order_id' => $order->id,
                'seller_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Questo ordine ha già un tracking. Usa la sezione tracking per aggiornamenti.',
            ], 422);
        }

        // D5: state guard – solo stati “in attesa spedizione”
        $allowedStatuses = ['paid_funds_held', 'paid', 'confirmed', 'pending_payment', 'pending'];
        if (!in_array($order->status, $allowedStatuses, true)) {
            Log::warning('D5: markShipped rifiutato – stato ordine non valido', [
                'order_id' => $order->id,
                'seller_id' => $user->id,
                'current_status' => $order->status,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Segnare come spedito è consentito solo per ordini in attesa di spedizione.',
                'current_status' => $order->status,
            ], 409);
        }

        // D5: mark shipped consentito se metodo = UNTRACKED_STANDARD oppure ordine legacy (nessun order_shippings)
        $order->load(['orderShippings' => fn ($q) => $q->where('seller_id', $user->id)]);
        $sellerShipping = $order->orderShippings->first();
        $allowUntracked = $sellerShipping === null
            || $sellerShipping->shipping_method === ShippingMethod::UNTRACKED_STANDARD;
        if (!$allowUntracked) {
            Log::warning('D5: markShipped rifiutato – metodo non untracked', [
                'order_id' => $order->id,
                'seller_id' => $user->id,
                'shipping_method' => $sellerShipping->shipping_method ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Questo ordine richiede l\'inserimento del tracking. Non è possibile segnare come spedito senza tracking.',
            ], 422);
        }

        $order->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        event(new \App\Events\OrderShippedUntracked($order->fresh(['seller', 'buyer'])));

        ShippingAuditLog::log(
            ShippingAuditLog::ACTION_SHIPPED_UNTRACKED,
            ShippingAuditLog::SOURCE_API,
            (int) $order->id,
            (int) $order->seller_id,
            (int) $order->buyer_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Spedizione registrata',
            'data' => [
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'status' => $order->status,
            ],
        ]);
    }

    private function findOrderForSeller(string $orderId, int $sellerId): ?Order
    {
        return Order::where('id', $orderId)
            ->where(function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId)
                    ->orWhereHas('orderItems.cardListing', fn ($q2) => $q2->where('seller_id', $sellerId));
            })
            ->first();
    }

    private function deriveShipmentStatus(Order $order): string
    {
        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            return 'CANCELLED';
        }
        if ($order->has_dispute || $order->status === 'dispute_hold') {
            return 'DISPUTED';
        }
        if (in_array($order->status, ['completed'], true) && $order->payout_status === 'paid') {
            return 'RELEASED';
        }
        if (in_array($order->status, ['delivered_pending_72h'], true)) {
            return 'DELIVERED_HOLD_72H';
        }
        if (in_array($order->status, ['delivered'], true)) {
            return 'DELIVERED_HOLD_72H';
        }
        if (in_array($order->status, ['shipped', 'in_transit_verified', 'label_created'], true)) {
            return 'SHIPPED_IN_TRANSIT';
        }
        if (in_array($order->status, ['paid_funds_held', 'confirmed', 'pending_payment', 'pending'], true)) {
            return 'PAID_WAITING_SHIPMENT';
        }
        return 'PAID_WAITING_SHIPMENT';
    }

    private function trackingDeadlineAt(Order $order): ?string
    {
        if (!$order->paid_at) {
            return null;
        }
        $days = (int) config('shipping.tracking_required_within_days', 7);
        return $order->paid_at->copy()->addDays($days)->toIso8601String();
    }
}
