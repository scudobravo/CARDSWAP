<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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
    public function show(int $id): JsonResponse
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
    public function getSellerOrders(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $orders = Order::where('seller_id', $user->id)
                ->with(['orderItems.cardListing.cardModel', 'buyer'])
                ->orderBy('created_at', 'desc')
                ->get();

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
    public function updateStatus(Request $request, int $id): JsonResponse
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

            $validator = \Validator::make($request->all(), [
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
}