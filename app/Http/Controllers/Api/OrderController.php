<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ShippingAuditLog;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AfterShipService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Ottieni tutti gli ordini dell'utente
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $orders = Order::where('buyer_id', $user->id)
                ->with(['orderItems.cardListing.cardModel', 'seller'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Ordini recuperati con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero degli ordini',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottieni i dettagli di un ordine specifico
     */
    public function show($id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            Log::info('Order details requested', [
                'order_id' => $id,
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
            
            $order = Order::where('buyer_id', $user->id)
                ->with([
                    'orderItems.cardListing.cardModel',
                    'orderItems.cardListing.seller',
                    'seller',
                    'orderShippings'
                ])
                ->find($id);

            if (!$order) {
                Log::warning('Order not found for user', [
                    'order_id' => $id,
                    'user_id' => $user->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Ordine non trovato'
                ], 404);
            }

            // Formatta i dati degli articoli per la risposta
            $orderItems = $order->orderItems->map(function ($item) {
                $listing = $item->cardListing;
                $cardModel = $listing->cardModel;
                $seller = $listing->seller;

                // Formatta l'immagine correttamente
                $image = null;
                if ($listing->images && is_array($listing->images) && count($listing->images) > 0) {
                    $imagePath = $listing->images[0];
                    // Se l'immagine non inizia con http o /storage/, aggiungi /storage/
                    if ($imagePath && !str_starts_with($imagePath, 'http') && !str_starts_with($imagePath, '/storage/')) {
                        $image = '/storage/' . ltrim($imagePath, '/');
                    } else {
                        $image = $imagePath;
                    }
                }

                return [
                    'id' => $item->id,
                    'name' => $cardModel ? $cardModel->name : 'Prodotto',
                    'condition' => $item->condition,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'seller_name' => $seller ? $seller->name : 'Venditore',
                    'image' => $image
                ];
            });

            Log::info('Order details retrieved successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'has_dispute' => $order->has_dispute,
                'payout_status' => $order->payout_status,
                'user_id' => $user->id
            ]);

            $orderArray = $order->toArray();
            $orderArray['shipment_status'] = $this->deriveShipmentStatusForBuyer($order);
            $orderArray['insurance_fee'] = (float) $order->orderShippings->sum('insurance_fee');
            $orderArray['is_tracked'] = !empty($order->tracking_number);

            return response()->json([
                'success' => true,
                'order' => $orderArray,
                'order_items' => $orderItems,
                'message' => 'Ordine recuperato con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nel recupero dettagli ordine', [
                'order_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dell\'ordine',
                'error' => config('app.debug') ? $e->getMessage() : 'Si è verificato un errore'
            ], 500);
        }
    }

    /**
     * Stato spedizione per UI buyer (CardSwap Shipping V1 FASE D2)
     */
    private function deriveShipmentStatusForBuyer(Order $order): string
    {
        if (in_array($order->status, ['cancelled'], true)) {
            return 'CANCELLED';
        }
        if (in_array($order->status, ['refunded'], true)) {
            return 'REFUNDED';
        }
        if ($order->has_dispute || $order->status === 'dispute_hold') {
            return 'DISPUTED';
        }
        if (in_array($order->status, ['completed'], true) && $order->payout_status === 'paid') {
            return 'RELEASED';
        }
        if (in_array($order->status, ['delivered_pending_72h', 'delivered'], true)) {
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

    /**
     * Ottieni gli ordini come venditore
     */
    public function getSellerOrders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            Log::info('getSellerOrders called', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
            
            // Prima verifica: conta gli orderItems di questo venditore
            $orderItemsCount = \App\Models\OrderItem::whereHas('cardListing', function ($q) use ($user) {
                $q->where('seller_id', $user->id);
            })->count();
            
            Log::info('OrderItems count for seller', [
                'user_id' => $user->id,
                'order_items_count' => $orderItemsCount
            ]);
            
            // Cerca ordini in due modi:
            // 1. Ordini dove questo venditore è il venditore principale (seller_id)
            // 2. Ordini che hanno almeno un orderItem con cardListing di questo venditore (multi-vendor)
            $query = Order::where(function ($q) use ($user) {
                $q->where('seller_id', $user->id)
                  ->orWhereHas('orderItems', function ($q2) use ($user) {
                      $q2->whereHas('cardListing', function ($q3) use ($user) {
                          $q3->where('seller_id', $user->id);
                      });
                  });
            })
            ->with([
                'orderItems' => function ($q) use ($user) {
                    // Carica solo gli orderItems di questo venditore
                    $q->whereHas('cardListing', function ($q2) use ($user) {
                        $q2->where('seller_id', $user->id);
                    })->with([
                        'cardListing.cardModel'
                        // images è un campo JSON, non una relazione, quindi non serve eager loading
                    ]);
                },
                'buyer',
                'seller.defaultAddress'
            ]);

            // Filtri
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Paginazione
            $perPage = $request->get('per_page', 15);
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

            Log::info('getSellerOrders result', [
                'user_id' => $user->id,
                'orders_count' => $orders->count(),
                'total' => $orders->total(),
                'first_order_id' => $orders->first()?->id,
                'first_order_number' => $orders->first()?->order_number
            ]);
            
            // Debug: verifica il primo ordine se esiste
            if ($orders->count() > 0) {
                $firstOrder = $orders->first();
                $firstOrderItems = $firstOrder->orderItems ?? [];
                Log::info('First order details', [
                    'order_id' => $firstOrder->id,
                    'order_number' => $firstOrder->order_number,
                    'order_items_count' => count($firstOrderItems),
                    'order_items_ids' => collect($firstOrderItems)->pluck('id')->toArray()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Ordini venditore recuperati con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore in getSellerOrders', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero degli ordini venditore',
                'error' => config('app.debug') ? $e->getMessage() : 'Si è verificato un errore'
            ], 500);
        }
    }

    /**
     * Conferma il pagamento di un ordine (per acquirenti)
     */
    public function confirmPayment($id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $order = Order::where('buyer_id', $user->id)->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ordine non trovato'
                ], 404);
            }

            // Verifica lo stato corrente - potrebbe essere già aggiornato dal webhook
            $validStatuses = ['pending_payment', 'paid_funds_held'];
            if (!in_array($order->status, $validStatuses)) {
                Log::warning('Order status not valid for confirmPayment', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'current_status' => $order->status,
                    'user_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Lo stato dell\'ordine non consente la conferma del pagamento',
                    'order' => $order
                ], 400);
            }

            // Aggiorna solo se è ancora pending_payment (se è già paid_funds_held, il webhook è arrivato prima)
            if ($order->status === 'pending_payment') {
                Log::info('Confirming payment from frontend (webhook not arrived yet)', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $user->id
                ]);

                $order->update([
                    'status' => 'paid_funds_held', // Nuovo stato: fondi pagati ma trattenuti
                    'paid_at' => now()
                ]);

                event(new \App\Events\OrderPaid($order->fresh(['seller', 'buyer'])));

                Log::info('Order updated to paid_funds_held from frontend confirmPayment', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pagamento confermato',
                    'order' => $order
                ]);
            }

            // Se è già paid_funds_held, il webhook è arrivato prima - va bene
            Log::info('Order already in paid_funds_held (webhook arrived first)', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento già confermato',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nella conferma pagamento', [
                'order_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nella conferma del pagamento',
                'error' => config('app.debug') ? $e->getMessage() : 'Si è verificato un errore'
            ], 500);
        }
    }

    /**
     * Apre una dispute per un ordine (solo per acquirenti)
     */
    public function openDispute(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Valida i dati della richiesta PRIMA di accedere al database
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:1000',
                'description' => 'nullable|string|max:5000'
            ]);

            if ($validator->fails()) {
                Log::warning('Validazione dispute fallita', [
                    'order_id' => $id,
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Dati di validazione non validi',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Usa transazione DB con lock pessimistico per evitare race conditions
            return DB::transaction(function () use ($request, $id, $user) {
                // Lock pessimistico per evitare race condition con ReleaseSellerFunds
                $order = Order::where('buyer_id', $user->id)
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    Log::warning('Tentativo di aprire dispute su ordine non trovato', [
                        'order_id' => $id,
                        'user_id' => $user->id
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Ordine non trovato'
                    ], 404);
                }

                // D5: dispute vietata se ordine = RELEASED (payout già pagato)
                if ($order->status !== 'delivered_pending_72h') {
                    Log::warning('D5: openDispute rifiutato – stato non valido', [
                        'order_id' => $order->id,
                        'buyer_id' => $user->id,
                        'current_status' => $order->status,
                        'payout_status' => $order->payout_status
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Non è possibile aprire una dispute per questo ordine. Le dispute possono essere aperte solo per ordini consegnati e in attesa di rilascio fondi.',
                        'current_status' => $order->status
                    ], 409);
                }

                if ($order->payout_status === 'paid') {
                    Log::warning('D5: openDispute rifiutato – pagamento già rilasciato', [
                        'order_id' => $order->id,
                        'buyer_id' => $user->id,
                        'payout_completed_at' => $order->payout_completed_at
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Non è possibile aprire una dispute per questo ordine. Il pagamento al venditore è già stato completato.',
                        'payout_completed_at' => $order->payout_completed_at
                    ], 409);
                }

                // D5: una sola disputa per ordine
                if ($order->has_dispute) {
                    Log::warning('D5: openDispute rifiutato – dispute già aperta', [
                        'order_id' => $order->id,
                        'buyer_id' => $user->id,
                        'dispute_opened_at' => $order->dispute_opened_at
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Una dispute è già aperta per questo ordine',
                        'dispute_opened_at' => $order->dispute_opened_at
                    ], 409);
                }

                // D5: insured → flag “documentazione richiesta” in caso di disputa (backend only)
                $order->load('orderShippings');
                $insuranceFee = (float) $order->orderShippings->sum('insurance_fee');
                $notesBase = ($order->notes ?? '') . "\n[DISPUTE APERTA] " . now()->format('Y-m-d H:i:s') . "\nMotivo: " . $request->reason . ($request->description ? "\nDescrizione: " . $request->description : '');
                if ($insuranceFee > 0) {
                    $notesBase .= "\n[DISPUTE] Documentazione richiesta (ordine assicurato).";
                }

                $order->update([
                    'has_dispute' => true,
                    'dispute_opened_at' => now(),
                    'status' => 'dispute_hold',
                    'payout_status' => 'dispute_hold',
                    'notes' => $notesBase,
                ]);

                event(new \App\Events\DisputeOpened($order->fresh(['seller', 'buyer'])));

                ShippingAuditLog::log(
                    ShippingAuditLog::ACTION_DISPUTE_OPENED,
                    ShippingAuditLog::SOURCE_API,
                    (int) $order->id,
                    (int) $order->seller_id,
                    (int) $user->id,
                    ['insured' => $insuranceFee > 0]
                );

                Log::info('Dispute aperta per ordine con successo', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'buyer_id' => $user->id,
                    'buyer_email' => $user->email,
                    'seller_id' => $order->seller_id,
                    'reason' => $request->reason,
                    'description_length' => strlen($request->description ?? ''),
                    'dispute_opened_at' => $order->dispute_opened_at,
                    'previous_status' => 'delivered_pending_72h',
                    'previous_payout_status' => 'pending_payout',
                    'payout_scheduled_at' => $order->payout_scheduled_at,
                    'seller_payout_amount' => $order->seller_payout_amount
                ]);

                // TODO: Invia notifica email al venditore e all'admin
                // TODO: Crea notifica in-app per venditore

                return response()->json([
                    'success' => true,
                    'message' => 'Dispute aperta con successo. Il payout al venditore è stato bloccato.',
                    'order' => $order->fresh()
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Errore nell\'apertura dispute', [
                'order_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'apertura della dispute',
                'error' => config('app.debug') ? $e->getMessage() : 'Si è verificato un errore'
            ], 500);
        }
    }

    /**
     * Aggiorna lo stato di un ordine (solo per venditori)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $order = Order::where('seller_id', $user->id)->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ordine non trovato'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:pending,pending_payment,paid_funds_held,label_created,in_transit_verified,delivered_pending_72h,dispute_hold,completed,confirmed,shipped,delivered,cancelled,refunded',
                'tracking_number' => 'nullable|string|max:255',
                'carrier_code' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dati di validazione non validi',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [
                'status' => $request->input('status')
            ];

            if ($request->input('tracking_number')) {
                $updateData['tracking_number'] = $request->input('tracking_number');
            }
            if ($request->filled('carrier_code')) {
                $updateData['carrier_code'] = $request->input('carrier_code');
            }

            if ($request->input('notes')) {
                $updateData['notes'] = $request->input('notes');
            }

            // Imposta la data di spedizione se lo stato è "shipped"
            if ($request->input('status') === 'shipped') {
                $updateData['shipped_at'] = now();
            }

            // Imposta la data di consegna se lo stato è "delivered"
            if ($request->input('status') === 'delivered') {
                $updateData['delivered_at'] = now();
            }

            $order->update($updateData);

            // Registra il tracking su AfterShip quando il venditore inserisce il numero (CardSwap V1 - unica fonte tracking)
            if ($request->input('tracking_number')) {
                $slug = $request->input('carrier_code') ?: $order->carrier_code;
                app(AfterShipService::class)->createTracking($order, $request->input('tracking_number'), $slug ?: null);
            }

            // Invia notifica all'acquirente
            $this->sendOrderStatusNotification($order, $request->input('status'));

            return response()->json([
                'success' => true,
                'order' => $order,
                'message' => 'Stato ordine aggiornato con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento dello stato dell\'ordine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invia notifica per aggiornamento stato ordine
     */
    private function sendOrderStatusNotification(Order $order, string $status): void
    {
        try {
            $buyer = $order->buyer;
            if (!$buyer) return;

            $statusMessages = [
                'confirmed' => 'Il tuo ordine #' . $order->order_number . ' è stato confermato e sarà preparato per la spedizione.',
                'shipped' => 'Il tuo ordine #' . $order->order_number . ' è stato spedito!' . 
                           ($order->tracking_number ? ' Numero di tracking: ' . $order->tracking_number : ''),
                'delivered' => 'Il tuo ordine #' . $order->order_number . ' è stato consegnato con successo!',
                'cancelled' => 'Il tuo ordine #' . $order->order_number . ' è stato cancellato.'
            ];

            $message = $statusMessages[$status] ?? 'Lo stato del tuo ordine #' . $order->order_number . ' è stato aggiornato.';

            // Crea notifica nel database
            $buyer->notifications()->create([
                'type' => 'order_status_update',
                'title' => 'Aggiornamento Ordine #' . $order->order_number,
                'message' => $message,
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $status,
                    'tracking_number' => $order->tracking_number
                ]
            ]);

            // TODO: Invia email di notifica
            // Mail::to($buyer->email)->send(new OrderStatusUpdate($order, $status));

        } catch (\Exception $e) {
            Log::error('Errore nell\'invio notifica ordine: ' . $e->getMessage());
        }
    }

    /**
     * Ottieni statistiche ordini venditore
     */
    public function getSellerStatistics(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Usa whereHas per contare ordini con prodotti di questo venditore (gestisce multi-vendor)
            $baseQuery = function ($query) use ($user) {
                return $query->whereHas('orderItems.cardListing', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                });
            };
            
            $stats = [
                'pending' => Order::whereHas('orderItems.cardListing', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })->where('status', 'pending')->count(),
                'shipped' => Order::whereHas('orderItems.cardListing', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })->where('status', 'shipped')->count(),
                'delivered' => Order::whereHas('orderItems.cardListing', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })->where('status', 'delivered')->count(),
                'cancelled' => Order::whereHas('orderItems.cardListing', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })->where('status', 'cancelled')->count(),
                'total_sales' => Order::whereHas('orderItems.cardListing', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })->whereIn('status', ['delivered', 'shipped', 'completed', 'delivered_pending_72h'])
                    ->sum('total_amount'),
                'total_orders' => Order::whereHas('orderItems.cardListing', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })->count(),
                'this_month_orders' => Order::whereHas('orderItems.cardListing', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'this_month_sales' => Order::whereHas('orderItems.cardListing', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })->whereIn('status', ['delivered', 'shipped', 'completed', 'delivered_pending_72h'])
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_amount')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiche ordini recuperate con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore in getSellerStatistics', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero delle statistiche',
                'error' => config('app.debug') ? $e->getMessage() : 'Si è verificato un errore'
            ], 500);
        }
    }

    /**
     * Ottieni statistiche dettagliate per venditore (alias per compatibilità)
     */
    public function getDetailedStatistics(Request $request): JsonResponse
    {
        $salesController = new \App\Http\Controllers\Api\SalesStatisticsController();
        return $salesController->getSalesStatistics($request);
    }
}