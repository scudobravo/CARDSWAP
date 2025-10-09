<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CardListing;
use App\Models\OrderFeedback;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesStatisticsController extends Controller
{
    /**
     * Ottieni statistiche dettagliate per venditore
     */
    public function getSalesStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 30);
            $category = $request->get('category') ?? '';
            
            $endDate = now();
            $startDate = $endDate->copy()->subDays($period);
            $previousStartDate = $startDate->copy()->subDays($period);
            $previousEndDate = $startDate->copy();

            // Query base per ordini venditore
            $ordersQuery = Order::where('seller_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Filtro per categoria se specificato
            if ($category) {
                $ordersQuery->whereHas('orderItems.cardListing.cardModel.category', function ($q) use ($category) {
                    $q->where('name', $category);
                });
            }

            $orders = $ordersQuery->get();

            // Statistiche periodo precedente per confronto
            $previousOrdersQuery = Order::where('seller_id', $user->id)
                ->whereBetween('created_at', [$previousStartDate, $previousEndDate]);

            if ($category) {
                $previousOrdersQuery->whereHas('orderItems.cardListing.cardModel.category', function ($q) use ($category) {
                    $q->where('name', $category);
                });
            }

            $previousOrders = $previousOrdersQuery->get();

            // Calcola statistiche principali
            $totalSales = $orders->whereIn('status', ['delivered', 'shipped'])->sum('total_amount');
            $totalOrders = $orders->count();
            $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

            // Statistiche periodo precedente
            $previousTotalSales = $previousOrders->whereIn('status', ['delivered', 'shipped'])->sum('total_amount');
            $previousTotalOrders = $previousOrders->count();
            $previousAverageOrderValue = $previousTotalOrders > 0 ? $previousTotalSales / $previousTotalOrders : 0;

            // Calcola percentuali di cambiamento
            $salesChange = $previousTotalSales > 0 ? (($totalSales - $previousTotalSales) / $previousTotalSales) * 100 : 0;
            $ordersChange = $previousTotalOrders > 0 ? (($totalOrders - $previousTotalOrders) / $previousTotalOrders) * 100 : 0;
            $aovChange = $previousAverageOrderValue > 0 ? (($averageOrderValue - $previousAverageOrderValue) / $previousAverageOrderValue) * 100 : 0;

            // Statistiche ordini per stato
            $ordersByStatus = [
                'pending' => $orders->where('status', 'pending')->count(),
                'confirmed' => $orders->where('status', 'confirmed')->count(),
                'shipped' => $orders->where('status', 'shipped')->count(),
                'delivered' => $orders->where('status', 'delivered')->count(),
                'cancelled' => $orders->where('status', 'cancelled')->count(),
            ];

            // Top prodotti venduti
            $topProducts = $this->getTopProducts($user->id, $startDate, $endDate, $category);

            // Vendite per categoria
            $categorySales = $this->getCategorySales($user->id, $startDate, $endDate);

            // Vendite giornaliere per grafico
            $dailySales = $this->getDailySales($user->id, $startDate, $endDate, $category);

            // Trend mensili
            $monthlyTrend = $this->getMonthlyTrend($user->id, $period, $category);

            // Rating medio e feedback
            $feedbackStats = $this->getFeedbackStats($user->id);

            $statistics = [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'average_order_value' => $averageOrderValue,
                'sales_change' => $salesChange,
                'orders_change' => $ordersChange,
                'aov_change' => $aovChange,
                'orders_by_status' => $ordersByStatus,
                'top_products' => $topProducts,
                'category_sales' => $categorySales,
                'daily_sales' => $dailySales,
                'monthly_trend' => $monthlyTrend,
                'average_rating' => $feedbackStats['average_rating'],
                'total_feedbacks' => $feedbackStats['total_feedbacks'],
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Statistiche vendite recuperate con successo'
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
     * Ottieni top prodotti venduti
     */
    private function getTopProducts(int $sellerId, Carbon $startDate, Carbon $endDate, ?string $category = null): array
    {
        $query = OrderItem::whereHas('order', function ($q) use ($sellerId, $startDate, $endDate) {
            $q->where('seller_id', $sellerId)
              ->whereBetween('created_at', [$startDate, $endDate])
              ->whereIn('status', ['delivered', 'shipped']);
        })
        ->with(['cardListing.cardModel'])
        ->select('card_listing_id', DB::raw('SUM(quantity) as quantity_sold'), DB::raw('SUM(total_price) as total_revenue'))
        ->groupBy('card_listing_id')
        ->orderBy('quantity_sold', 'desc')
        ->limit(10);

        if ($category) {
            $query->whereHas('cardListing.cardModel.category', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        return $query->get()->map(function ($item) {
            $cardModel = $item->cardListing->cardModel;
            return [
                'id' => $item->card_listing_id,
                'name' => $cardModel ? $cardModel->name : 'Prodotto',
                'category' => $cardModel ? $cardModel->category : 'N/A',
                'quantity_sold' => $item->quantity_sold,
                'total_revenue' => $item->total_revenue,
            ];
        })->toArray();
    }

    /**
     * Ottieni vendite per categoria
     */
    private function getCategorySales(int $sellerId, Carbon $startDate, Carbon $endDate): array
    {
        $categorySales = OrderItem::whereHas('order', function ($q) use ($sellerId, $startDate, $endDate) {
            $q->where('seller_id', $sellerId)
              ->whereBetween('created_at', [$startDate, $endDate])
              ->whereIn('status', ['delivered', 'shipped']);
        })
        ->join('card_listings', 'order_items.card_listing_id', '=', 'card_listings.id')
        ->join('card_models', 'card_listings.card_model_id', '=', 'card_models.id')
        ->join('categories', 'card_models.category_id', '=', 'categories.id')
        ->select('categories.name as category', DB::raw('SUM(order_items.total_price) as total_sales'))
        ->groupBy('categories.name')
        ->get()
        ->pluck('total_sales', 'category')
        ->toArray();

        return $categorySales;
    }

    /**
     * Ottieni vendite giornaliere per grafico
     */
    private function getDailySales(int $sellerId, Carbon $startDate, Carbon $endDate, ?string $category = null): array
    {
        $query = Order::where('seller_id', $sellerId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['delivered', 'shipped'])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as sales'))
            ->groupBy('date')
            ->orderBy('date');

        if ($category) {
            $query->whereHas('orderItems.cardListing.cardModel.category', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        return $query->get()->map(function ($item) {
            return [
                'date' => $item->date,
                'sales' => $item->sales,
            ];
        })->toArray();
    }

    /**
     * Ottieni trend mensili
     */
    private function getMonthlyTrend(int $sellerId, int $period, ?string $category = null): array
    {
        $months = [];
        $currentDate = now();
        
        // Calcola il numero di mesi da mostrare
        $monthsToShow = max(6, ceil($period / 30));
        
        for ($i = $monthsToShow - 1; $i >= 0; $i--) {
            $monthStart = $currentDate->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $currentDate->copy()->subMonths($i)->endOfMonth();
            
            $query = Order::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereIn('status', ['delivered', 'shipped']);

            if ($category) {
                $query->whereHas('orderItems.cardListing.cardModel.category', function ($q) use ($category) {
                    $q->where('name', $category);
                });
            }

            $orders = $query->get();
            $sales = $orders->sum('total_amount');
            $orderCount = $orders->count();
            $averageOrderValue = $orderCount > 0 ? $sales / $orderCount : 0;

            $months[] = [
                'month' => $monthStart->format('Y-m'),
                'orders' => $orderCount,
                'sales' => $sales,
                'average_order_value' => $averageOrderValue,
            ];
        }

        return $months;
    }

    /**
     * Ottieni statistiche feedback
     */
    private function getFeedbackStats(int $sellerId): array
    {
        $feedbacks = OrderFeedback::where('seller_id', $sellerId)
            ->where('is_public', true)
            ->where('is_hidden', false)
            ->get();

        $totalFeedbacks = $feedbacks->count();
        $averageRating = $totalFeedbacks > 0 ? $feedbacks->avg('rating') : 0;

        return [
            'total_feedbacks' => $totalFeedbacks,
            'average_rating' => $averageRating,
        ];
    }
}
