<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderFeedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesFeedbackController extends Controller
{
    /**
     * Ottieni feedback per venditore autenticato
     */
    public function getSellerFeedbacks(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $query = OrderFeedback::where('seller_id', $user->id)
                ->with(['buyer', 'order.orderItems.cardListing.cardModel'])
                ->orderBy('created_at', 'desc');

            // Filtri
            if ($request->has('rating') && $request->rating !== '') {
                $query->where('rating', $request->rating);
            }

            if ($request->has('period') && $request->period !== '') {
                $days = (int) $request->period;
                $startDate = now()->subDays($days);
                $query->where('created_at', '>=', $startDate);
            }

            // Paginazione
            $perPage = $request->get('per_page', 15);
            $feedbacks = $query->paginate($perPage);

            // Calcola statistiche venditore
            $stats = $this->calculateSellerStats($user->id);

            return response()->json([
                'success' => true,
                'data' => $feedbacks,
                'seller' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'average_rating' => $stats['average_rating'],
                    'total_feedbacks' => $stats['total_feedbacks'],
                    'rating_breakdown' => $stats['rating_breakdown'],
                ],
                'message' => 'Feedback venditore recuperati con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dei feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rispondi a un feedback
     */
    public function respondToFeedback(Request $request, int $feedbackId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'response' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dati non validi',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $feedback = OrderFeedback::where('id', $feedbackId)
                ->where('seller_id', $user->id)
                ->first();

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feedback non trovato'
                ], 404);
            }

            // Aggiorna il feedback con la risposta del venditore
            $feedback->update([
                'seller_response' => $request->response,
                'seller_response_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Risposta inviata con successo',
                'feedback' => $feedback->fresh()->load(['buyer', 'order'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'invio della risposta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottieni statistiche dettagliate feedback venditore
     */
    public function getFeedbackStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 30);
            
            $endDate = now();
            $startDate = $endDate->copy()->subDays($period);

            // Statistiche generali
            $totalFeedbacks = OrderFeedback::where('seller_id', $user->id)->count();
            $averageRating = OrderFeedback::where('seller_id', $user->id)->avg('rating') ?: 0;
            
            // Breakdown per rating
            $ratingBreakdown = [];
            for ($i = 1; $i <= 5; $i++) {
                $ratingBreakdown[$i] = OrderFeedback::where('seller_id', $user->id)
                    ->where('rating', $i)
                    ->count();
            }

            // Feedback con risposta
            $feedbacksWithResponse = OrderFeedback::where('seller_id', $user->id)
                ->whereNotNull('seller_response')
                ->count();

            // Feedback recenti (ultimo periodo)
            $recentFeedbacks = OrderFeedback::where('seller_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Trend mensili
            $monthlyTrend = $this->getMonthlyFeedbackTrend($user->id, 6);

            // Top feedback (migliori e peggiori)
            $topFeedbacks = OrderFeedback::where('seller_id', $user->id)
                ->with(['buyer', 'order'])
                ->orderBy('rating', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $worstFeedbacks = OrderFeedback::where('seller_id', $user->id)
                ->with(['buyer', 'order'])
                ->orderBy('rating', 'asc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $statistics = [
                'total_feedbacks' => $totalFeedbacks,
                'average_rating' => round($averageRating, 2),
                'rating_breakdown' => $ratingBreakdown,
                'feedbacks_with_response' => $feedbacksWithResponse,
                'response_rate' => $totalFeedbacks > 0 ? round(($feedbacksWithResponse / $totalFeedbacks) * 100, 1) : 0,
                'recent_feedbacks' => $recentFeedbacks,
                'monthly_trend' => $monthlyTrend,
                'top_feedbacks' => $topFeedbacks,
                'worst_feedbacks' => $worstFeedbacks,
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Statistiche feedback recuperate con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero delle statistiche feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcola statistiche venditore
     */
    private function calculateSellerStats(int $sellerId): array
    {
        $feedbacks = OrderFeedback::where('seller_id', $sellerId);

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
     * Ottieni trend mensili feedback
     */
    private function getMonthlyFeedbackTrend(int $sellerId, int $months = 6): array
    {
        $trend = [];
        $currentDate = now();
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $currentDate->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $currentDate->copy()->subMonths($i)->endOfMonth();
            
            $monthFeedbacks = OrderFeedback::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->get();

            $averageRating = $monthFeedbacks->count() > 0 ? $monthFeedbacks->avg('rating') : 0;

            $trend[] = [
                'month' => $monthStart->format('Y-m'),
                'total_feedbacks' => $monthFeedbacks->count(),
                'average_rating' => round($averageRating, 2),
                'five_star_count' => $monthFeedbacks->where('rating', 5)->count(),
                'one_star_count' => $monthFeedbacks->where('rating', 1)->count(),
            ];
        }

        return $trend;
    }
}
