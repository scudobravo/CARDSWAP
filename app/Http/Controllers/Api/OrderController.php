<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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
            
            $order = Order::where('buyer_id', $user->id)
                ->with([
                    'orderItems.cardListing.cardModel',
                    'orderItems.cardListing.seller',
                    'seller'
                ])
                ->find($id);

            if (!$order) {
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

                return [
                    'id' => $item->id,
                    'name' => $cardModel ? $cardModel->name : 'Prodotto',
                    'condition' => $item->condition,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'seller_name' => $seller ? $seller->name : 'Venditore',
                    'image' => $listing->images ? $listing->images[0] : null
                ];
            });

            return response()->json([
                'success' => true,
                'order' => $order,
                'order_items' => $orderItems,
                'message' => 'Ordine recuperato con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dell\'ordine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottieni gli ordini come venditore
     */
    public function getSellerOrders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $query = Order::where('seller_id', $user->id)
                ->with(['orderItems.cardListing.cardModel', 'buyer']);

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

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Ordini venditore recuperati con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero degli ordini venditore',
                'error' => $e->getMessage()
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
                'status' => 'required|string|in:pending,shipped,delivered,cancelled',
                'tracking_number' => 'nullable|string|max:255',
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
            
            $stats = [
                'pending' => Order::where('seller_id', $user->id)->where('status', 'pending')->count(),
                'shipped' => Order::where('seller_id', $user->id)->where('status', 'shipped')->count(),
                'delivered' => Order::where('seller_id', $user->id)->where('status', 'delivered')->count(),
                'cancelled' => Order::where('seller_id', $user->id)->where('status', 'cancelled')->count(),
                'total_sales' => Order::where('seller_id', $user->id)
                    ->whereIn('status', ['delivered', 'shipped'])
                    ->sum('total_amount'),
                'total_orders' => Order::where('seller_id', $user->id)->count(),
                'this_month_orders' => Order::where('seller_id', $user->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'this_month_sales' => Order::where('seller_id', $user->id)
                    ->whereIn('status', ['delivered', 'shipped'])
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
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero delle statistiche',
                'error' => $e->getMessage()
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