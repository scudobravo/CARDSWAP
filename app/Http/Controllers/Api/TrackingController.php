<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    /**
     * Aggiunge un evento di tracking a un ordine (per corrieri o admin)
     */
    public function addEvent(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'occurred_at' => 'nullable|date',
            'carrier_code' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Solo admin o venditore associato all'ordine
        $user = Auth::user();
        if ($user->role !== 'admin' && !$order->getSellers()->contains('id', $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorizzato'
            ], 403);
        }

        $data = $validator->validated();
        $data['order_id'] = $order->id;
        if (empty($data['occurred_at'])) {
            $data['occurred_at'] = now();
        }

        $event = OrderTrackingEvent::create($data);

        // opzionale: cambio di stato automatico su determinati status
        if (!empty($data['status'])) {
            $autoMap = [
                'in_transit' => 'shipped',
                'delivered' => 'delivered',
            ];
            if (isset($autoMap[$data['status']]) && $order->status !== $autoMap[$data['status']]) {
                $order->update(['status' => $autoMap[$data['status']]]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

    /**
     * Restituisce lo storico tracking di un ordine
     */
    public function history(Order $order): JsonResponse
    {
        $order->load('trackingEvents');
        return response()->json([
            'success' => true,
            'data' => $order->trackingEvents()->orderBy('occurred_at')->get(),
        ]);
    }
}


