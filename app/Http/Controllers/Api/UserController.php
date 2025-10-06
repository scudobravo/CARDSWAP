<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPaymentMethod;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Ottieni il profilo dell'utente autenticato
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load([
            'addresses',
            'paymentMethods',
            'notifications' => function($query) {
                $query->unread()->ordered()->limit(10);
            },
            'kycDocuments' => function($query) {
                $query->latest();
            }
        ]);

        return response()->json([
            'user' => $user,
            'stats' => [
                'total_orders' => $user->buyerOrders()->count() + $user->sellerOrders()->count(),
                'total_listings' => $user->cardListings()->count(),
                'total_wishlist_items' => $user->wishlistItems()->count(),
                'unread_notifications' => $user->unread_notifications_count,
            ]
        ]);
    }

    /**
     * Aggiorna il profilo dell'utente
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'fiscal_code' => 'sometimes|nullable|string|max:20',
            'birth_date' => 'sometimes|nullable|date|before:today',
            'birth_place' => 'sometimes|nullable|string|max:255',
            'nationality' => 'sometimes|nullable|string|max:100',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:100',
            'postal_code' => 'sometimes|nullable|string|max:10',
            'country' => 'sometimes|nullable|string|max:100',
            'bio' => 'sometimes|nullable|string|max:1000',
            'language' => 'sometimes|in:it,en,de,fr,es',
            'timezone' => 'sometimes|string|max:50',
            'currency' => 'sometimes|in:EUR,USD,GBP',
            'notification_preferences' => 'sometimes|array',
            'notification_preferences.email' => 'sometimes|boolean',
            'notification_preferences.push' => 'sometimes|boolean',
            'notification_preferences.sms' => 'sometimes|boolean',
        ]);

        // Se vengono forniti first_name e last_name, aggiorna anche name
        $updateData = $request->only([
            'name', 'first_name', 'last_name', 'username', 'phone', 'fiscal_code', 'birth_date', 'birth_place', 
            'nationality', 'address', 'city', 'postal_code', 'country', 'bio', 'language', 'timezone', 'currency', 'notification_preferences'
        ]);

        if ($request->has('first_name') && $request->has('last_name')) {
            $updateData['name'] = trim($request->first_name . ' ' . $request->last_name);
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'Profilo aggiornato con successo',
            'user' => $user->fresh()
        ]);
    }

    /**
     * Aggiorna la password dell'utente
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Password attuale non corretta'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password aggiornata con successo'
        ]);
    }

    /**
     * Aggiorna l'avatar dell'utente
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $user = $request->user();

        // Rimuovi avatar precedente se esiste
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Salva nuovo avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Avatar aggiornato con successo',
            'avatar_url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Rimuovi l'avatar dell'utente
     */
    public function removeAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        return response()->json([
            'message' => 'Avatar rimosso con successo'
        ]);
    }

    /**
     * Aggiorna le informazioni business per venditori
     */
    public function updateBusinessInfo(Request $request)
    {
        $user = $request->user();

        if (!$user->isSeller()) {
            return response()->json([
                'message' => 'Solo i venditori possono aggiornare le informazioni business'
            ], 403);
        }

        $request->validate([
            'business_name' => 'required|string|max:255',
            'vat_number' => 'required|string|max:50',
            'business_address' => 'required|string|max:500',
            'business_phone' => 'required|string|max:20',
            'business_description' => 'nullable|string|max:1000',
        ]);

        $user->update($request->only([
            'business_name', 'vat_number', 'business_address', 'business_phone', 'business_description'
        ]));

        return response()->json([
            'message' => 'Informazioni business aggiornate con successo',
            'user' => $user->fresh()
        ]);
    }

    /**
     * Ottieni le statistiche dell'utente
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = [
            'orders' => [
                'total' => $user->buyerOrders()->count() + $user->sellerOrders()->count(),
                'as_buyer' => $user->buyerOrders()->count(),
                'as_seller' => $user->sellerOrders()->count(),
                'pending' => $user->buyerOrders()->where('status', 'pending')->count(),
                'completed' => $user->buyerOrders()->where('status', 'completed')->count(),
            ],
            'listings' => [
                'total' => $user->cardListings()->count(),
                'active' => $user->cardListings()->where('status', 'active')->count(),
                'draft' => $user->cardListings()->where('status', 'draft')->count(),
                'sold' => $user->cardListings()->where('status', 'sold')->count(),
            ],
            'wishlist' => [
                'total_items' => $user->wishlistItems()->count(),
                'recent_additions' => $user->wishlistItems()->latest()->limit(5)->count(),
            ],
            'notifications' => [
                'unread' => $user->unread_notifications_count,
                'total_today' => $user->notifications()->whereDate('created_at', today())->count(),
                'total_week' => $user->notifications()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            ],
            'kyc' => [
                'status' => $user->kyc_status,
                'submitted_at' => $user->kyc_submitted_at,
                'verified_at' => $user->kyc_verified_at,
                'can_sell' => $user->canSell(),
                'needs_kyc' => $user->needsKyc(),
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Ottieni i dati completi del dashboard utente
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Carica le statistiche
        $stats = [
            'orders' => [
                'total' => $user->buyerOrders()->count() + $user->sellerOrders()->count(),
                'as_buyer' => $user->buyerOrders()->count(),
                'as_seller' => $user->sellerOrders()->count(),
                'pending' => $user->buyerOrders()->where('status', 'pending')->count(),
                'completed' => $user->buyerOrders()->where('status', 'completed')->count(),
            ],
            'listings' => [
                'total' => $user->cardListings()->count(),
                'active' => $user->cardListings()->where('status', 'active')->count(),
                'draft' => $user->cardListings()->where('status', 'draft')->count(),
                'sold' => $user->cardListings()->where('status', 'sold')->count(),
            ],
            'wishlist' => [
                'total_items' => $user->wishlistItems()->count(),
                'recent_additions' => $user->wishlistItems()->latest()->limit(5)->count(),
            ],
            'notifications' => [
                'unread' => $user->unread_notifications_count,
                'total_today' => $user->notifications()->whereDate('created_at', today())->count(),
                'total_week' => $user->notifications()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            ],
            'kyc' => [
                'status' => $user->kyc_status,
                'submitted_at' => $user->kyc_submitted_at,
                'verified_at' => $user->kyc_verified_at,
                'can_sell' => $user->canSell(),
                'needs_kyc' => $user->needsKyc(),
            ]
        ];

        // Carica le attività recenti
        $activities = collect();

        // Ordini recenti
        $orders = $user->buyerOrders()->with('orderItems.cardListing.cardModel')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($order) {
                return [
                    'type' => 'order',
                    'title' => 'Ordine #' . $order->id,
                    'description' => $order->orderItems->count() . ' articoli',
                    'date' => $order->created_at,
                    'status' => $order->status,
                    'amount' => $order->total_amount,
                    'data' => $order
                ];
            });

        // Inserzioni recenti
        $listings = $user->cardListings()->with('cardModel')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($listing) {
                return [
                    'type' => 'listing',
                    'title' => $listing->cardModel->name ?? 'Carta',
                    'description' => 'Inserzione ' . ucfirst($listing->status),
                    'date' => $listing->created_at,
                    'status' => $listing->status,
                    'price' => $listing->price,
                    'data' => $listing
                ];
            });

        // Notifiche recenti
        $notifications = $user->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($notification) {
                return [
                    'type' => 'notification',
                    'title' => $notification->title,
                    'description' => $notification->message,
                    'date' => $notification->created_at,
                    'read' => $notification->read_at !== null,
                    'data' => $notification
                ];
            });

        // Combina tutte le attività
        $activities = $activities
            ->merge($orders)
            ->merge($listings)
            ->merge($notifications)
            ->sortByDesc('date')
            ->take(10);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'activities' => $activities->values(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ]
            ]
        ]);
    }

    /**
     * Ottieni la cronologia attività dell'utente
     */
    public function activity(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);

        $activities = collect();

        // Ordini recenti
        $orders = $user->buyerOrders()->with('orderItems.cardListing.cardModel')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($order) {
                return [
                    'type' => 'order',
                    'title' => 'Ordine #' . $order->id,
                    'description' => $order->orderItems->count() . ' articoli',
                    'date' => $order->created_at,
                    'status' => $order->status,
                    'data' => $order
                ];
            });

        // Inserzioni recenti
        $listings = $user->cardListings()->with('cardModel')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($listing) {
                return [
                    'type' => 'listing',
                    'title' => 'Inserzione ' . $listing->cardModel->name,
                    'description' => 'Prezzo: €' . $listing->price,
                    'date' => $listing->created_at,
                    'status' => $listing->status,
                    'data' => $listing
                ];
            });

        // Wishlist recenti
        $wishlist = $user->wishlistItems()->with('cardModel')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'type' => 'wishlist',
                    'title' => 'Aggiunto a wishlist: ' . $item->cardModel->name,
                    'description' => 'Prezzo target: €' . $item->target_price,
                    'date' => $item->created_at,
                    'data' => $item
                ];
            });

        // Combina e ordina per data
        $activities = $orders->concat($listings)->concat($wishlist)
            ->sortByDesc('date')
            ->take($perPage);

        return response()->json([
            'activities' => $activities->values(),
            'pagination' => [
                'total' => $activities->count(),
                'per_page' => $perPage,
                'current_page' => 1,
                'last_page' => 1
            ]
        ]);
    }

    /**
     * Elimina l'account dell'utente
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE'
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Password non corretta'
            ], 400);
        }

        // Elimina tutti i token
        $user->tokens()->delete();

        // Elimina l'utente (cascade eliminerà tutti i dati correlati)
        $user->delete();

        return response()->json([
            'message' => 'Account eliminato con successo'
        ]);
    }

    /**
     * Ottieni le preferenze notifiche dell'utente
     */
    public function getNotificationPreferences(Request $request)
    {
        $user = $request->user();
        
        $preferences = $user->notification_preferences ?? [
            'email' => true,
            'push' => true,
            'sms' => false,
            'order_updates' => true,
            'price_alerts' => true,
            'new_messages' => true,
            'kyc_updates' => true,
            'promotions' => false
        ];

        return response()->json($preferences);
    }

    /**
     * Aggiorna le preferenze notifiche dell'utente
     */
    public function updateNotificationPreferences(Request $request)
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.email' => 'boolean',
            'preferences.push' => 'boolean',
            'preferences.sms' => 'boolean',
            'preferences.order_updates' => 'boolean',
            'preferences.price_alerts' => 'boolean',
            'preferences.new_messages' => 'boolean',
            'preferences.kyc_updates' => 'boolean',
            'preferences.promotions' => 'boolean',
        ]);

        $user = $request->user();
        $user->update(['notification_preferences' => $request->preferences]);

        return response()->json([
            'message' => 'Preferenze notifiche aggiornate con successo',
            'preferences' => $user->notification_preferences
        ]);
    }
}
