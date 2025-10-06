<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFeedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    /**
     * Lista feedback per un venditore
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sellerId = $request->integer('seller_id');
        $seller = User::findOrFail($sellerId);

        $feedbacks = OrderFeedback::query()
            ->where('seller_id', $sellerId)
            ->visible()
            ->with(['buyer', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        // Calcola statistiche venditore
        $stats = $this->calculateSellerStats($sellerId);

        return response()->json([
            'success' => true,
            'data' => $feedbacks,
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->display_name,
                'rating' => $stats['average_rating'],
                'total_feedbacks' => $stats['total_feedbacks'],
                'rating_breakdown' => $stats['rating_breakdown'],
            ]
        ]);
    }

    /**
     * Lascia feedback per un ordine
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'seller_id' => 'required|integer|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $order = Order::findOrFail($request->order_id);

        // Verifica che l'utente sia il buyer dell'ordine
        if ($order->buyer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorizzato'
            ], 403);
        }

        // Verifica che l'ordine sia in stato "delivered" o "refunded"
        if (!in_array($order->status, ['delivered', 'refunded'])) {
            return response()->json([
                'success' => false,
                'message' => 'Puoi lasciare feedback solo per ordini consegnati o rimborsati'
            ], 400);
        }

        // Verifica che non esista già un feedback per questo ordine-venditore
        $existingFeedback = OrderFeedback::where('order_id', $order->id)
            ->where('buyer_id', $user->id)
            ->where('seller_id', $request->seller_id)
            ->first();

        if ($existingFeedback) {
            return response()->json([
                'success' => false,
                'message' => 'Hai già lasciato feedback per questo ordine'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Crea il feedback
            $feedback = OrderFeedback::create([
                'order_id' => $order->id,
                'buyer_id' => $user->id,
                'seller_id' => $request->seller_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            // Aggiorna la media del venditore
            $this->updateSellerRating($request->seller_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Feedback lasciato con successo',
                'data' => $feedback->load(['buyer', 'order'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errore nel salvataggio del feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostra feedback specifico
     */
    public function show(OrderFeedback $feedback): JsonResponse
    {
        $feedback->load(['buyer', 'seller', 'order']);
        
        return response()->json([
            'success' => true,
            'data' => $feedback
        ]);
    }

    /**
     * Aggiorna feedback (solo se lasciato di recente)
     */
    public function update(Request $request, OrderFeedback $feedback): JsonResponse
    {
        $user = Auth::user();

        // Solo il buyer che ha lasciato il feedback può modificarlo
        if ($feedback->buyer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorizzato'
            ], 403);
        }

        // Solo se lasciato nelle ultime 24 ore
        if ($feedback->created_at->lt(now()->subDay())) {
            return response()->json([
                'success' => false,
                'message' => 'Non puoi più modificare questo feedback'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldRating = $feedback->rating;
            $feedback->update([
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            // Aggiorna la media del venditore solo se il rating è cambiato
            if ($oldRating !== $request->rating) {
                $this->updateSellerRating($feedback->seller_id);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Feedback aggiornato con successo',
                'data' => $feedback->fresh()->load(['buyer', 'order'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento del feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcola statistiche venditore
     */
    private function calculateSellerStats(int $sellerId): array
    {
        $feedbacks = OrderFeedback::where('seller_id', $sellerId)->visible();

        $totalFeedbacks = $feedbacks->count();
        $averageRating = $totalFeedbacks > 0 ? $feedbacks->avg('rating') : 0;

        $ratingBreakdown = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingBreakdown[$i] = $feedbacks->where('rating', $i)->count();
        }

        return [
            'total_feedbacks' => $totalFeedbacks,
            'average_rating' => round($averageRating, 2),
            'rating_breakdown' => $ratingBreakdown,
        ];
    }

    /**
     * Aggiorna la media rating del venditore
     */
    private function updateSellerRating(int $sellerId): void
    {
        $stats = $this->calculateSellerStats($sellerId);
        
        User::where('id', $sellerId)->update([
            'rating' => $stats['average_rating']
        ]);
    }
}
