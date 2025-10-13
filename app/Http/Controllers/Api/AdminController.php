<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\CardListing;
use App\Models\OrderFeedback;
use App\Models\ShippingZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        // Middleware sarà applicato nelle rotte
    }

    /**
     * Verifica se l'utente è admin
     */
    private function checkAdminAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->isAdmin();
    }

    /**
     * Dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }
        $now = now();
        $lastMonth = $now->copy()->subMonth();
        $lastWeek = $now->copy()->subWeek();

        // Statistiche generali
        $stats = [
            'users' => [
                'total' => User::count(),
                'buyers' => User::where('role', 'buyer')->count(),
                'sellers' => User::where('role', 'seller')->count(),
                'admins' => User::where('role', 'admin')->count(),
                'new_this_month' => User::where('created_at', '>=', $lastMonth)->count(),
                'new_this_week' => User::where('created_at', '>=', $lastWeek)->count(),
            ],
            'orders' => [
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'confirmed' => Order::where('status', 'confirmed')->count(),
                'shipped' => Order::where('status', 'shipped')->count(),
                'delivered' => Order::where('status', 'delivered')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
                'refunded' => Order::where('status', 'refunded')->count(),
                'total_revenue' => Order::where('status', 'delivered')->sum('total_amount'),
                'revenue_this_month' => Order::where('status', 'delivered')
                    ->where('created_at', '>=', $lastMonth)
                    ->sum('total_amount'),
            ],
            'listings' => [
                'total' => CardListing::count(),
                'active' => CardListing::where('status', 'active')->count(),
                'draft' => CardListing::where('status', 'draft')->count(),
                'sold' => CardListing::where('status', 'sold')->count(),
            ],
            'kyc' => [
                'pending' => User::where('kyc_status', 'pending')->count(),
                'approved' => User::where('kyc_status', 'approved')->count(),
                'rejected' => User::where('kyc_status', 'rejected')->count(),
            ],
        ];

        // Grafici per gli ultimi 30 giorni
        $dailyStats = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'orders' => Order::whereDate('created_at', $date)->count(),
                'revenue' => Order::where('status', 'delivered')
                    ->whereDate('created_at', $date)
                    ->sum('total_amount'),
                'new_users' => User::whereDate('created_at', $date)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'daily_stats' => $dailyStats,
            ]
        ]);
    }

    /**
     * Lista utenti con filtri
     */
    public function users(Request $request): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }
        $query = User::query();

        // Filtri
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
        }

        if ($request->filled('is_suspended')) {
            $query->where('is_suspended', $request->boolean('is_suspended'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['buyerOrders', 'sellerOrders', 'receivedFeedbacks'])
                      ->orderBy('created_at', 'desc')
                      ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Dettaglio utente
     */
    public function user(User $user): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }
        $user->loadCount(['buyerOrders', 'sellerOrders', 'receivedFeedbacks', 'cardListings']);
        
        // Statistiche ordini
        $orderStats = [
            'total_orders' => $user->buyerOrders()->count(),
            'total_sales' => $user->sellerOrders()->count(),
            'total_revenue' => $user->sellerOrders()->where('status', 'delivered')->sum('total_amount'),
            'avg_order_value' => $user->buyerOrders()->avg('total_amount'),
        ];

        // Feedback ricevuti
        $feedbacks = $user->receivedFeedbacks()
            ->with('buyer')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'order_stats' => $orderStats,
                'recent_feedbacks' => $feedbacks,
            ]
        ]);
    }

    /**
     * Aggiorna stato utente
     */
    public function updateUserStatus(Request $request, User $user): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }
        $validator = Validator::make($request->all(), [
            'is_suspended' => 'boolean',
            'kyc_status' => 'in:pending,approved,rejected',
            'kyc_rejection_reason' => 'nullable|string|max:500',
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

            $updateData = [];
            
            if ($request->has('is_suspended')) {
                $updateData['is_suspended'] = $request->boolean('is_suspended');
            }

            if ($request->has('kyc_status')) {
                $updateData['kyc_status'] = $request->kyc_status;
                
                if ($request->kyc_status === 'approved') {
                    $updateData['kyc_verified_at'] = now();
                } elseif ($request->kyc_status === 'rejected') {
                    $updateData['kyc_rejection_reason'] = $request->kyc_rejection_reason;
                }
            }

            $user->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utente aggiornato con successo',
                'data' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento dell\'utente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista ordini con filtri
     */
    public function orders(Request $request): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }
        $query = Order::with(['buyer', 'orderItems.cardListing.cardModel']);

        // Filtri
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('buyer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
                       ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Dettaglio ordine
     */
    public function order(Order $order): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }
        $order->load([
            'buyer',
            'orderItems.cardListing.cardModel',
            'trackingEvents',
            'feedbacks'
        ]);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Lista feedback con moderazione
     */
    public function feedbacks(Request $request): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }
        $query = OrderFeedback::with(['buyer', 'seller', 'order']);

        // Filtri
        if ($request->filled('is_hidden')) {
            $query->where('is_hidden', $request->boolean('is_hidden'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhereHas('buyer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('seller', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $feedbacks = $query->orderBy('created_at', 'desc')
                          ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $feedbacks
        ]);
    }

    /**
     * Moderazione feedback
     */
    public function moderateFeedback(Request $request, OrderFeedback $feedback): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }
        $validator = Validator::make($request->all(), [
            'is_hidden' => 'required|boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feedback->update([
            'is_hidden' => $request->boolean('is_hidden'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback moderato con successo',
            'data' => $feedback->fresh()
        ]);
    }

    /**
     * Lista zone di spedizione
     */
    public function shippingZones(Request $request): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        $zones = ShippingZone::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $zones
        ]);
    }

    /**
     * Crea nuova zona di spedizione
     */
    public function createShippingZone(Request $request): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:2',
            'region' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'shipping_cost' => 'required|numeric|min:0',
            'delivery_days_min' => 'required|integer|min:1',
            'delivery_days_max' => 'required|integer|min:1|gte:delivery_days_min',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $zone = ShippingZone::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Zona di spedizione creata con successo',
                'data' => $zone
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione della zona di spedizione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna zona di spedizione
     */
    public function updateShippingZone(Request $request, ShippingZone $shippingZone): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:2',
            'region' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'shipping_cost' => 'required|numeric|min:0',
            'delivery_days_min' => 'required|integer|min:1',
            'delivery_days_max' => 'required|integer|min:1|gte:delivery_days_min',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $shippingZone->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Zona di spedizione aggiornata con successo',
                'data' => $shippingZone->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento della zona di spedizione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina zona di spedizione
     */
    public function deleteShippingZone(ShippingZone $shippingZone): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        try {
            // Verifica se la zona è utilizzata da inserzioni
            $listingsCount = $shippingZone->cardListings()->count();
            if ($listingsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossibile eliminare la zona: è utilizzata da {$listingsCount} inserzioni"
                ], 422);
            }

            $shippingZone->delete();

            return response()->json([
                'success' => true,
                'message' => 'Zona di spedizione eliminata con successo'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'eliminazione della zona di spedizione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica se esistono zone di spedizione
     */
    public function checkShippingZones(): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        $zonesCount = ShippingZone::where('is_active', true)->count();

        return response()->json([
            'success' => true,
            'has_zones' => $zonesCount > 0,
            'zones_count' => $zonesCount
        ]);
    }
}
