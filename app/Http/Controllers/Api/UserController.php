<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPaymentMethod;
use App\Models\UserNotification;
use App\Models\ShippingZone;
use App\Models\CardListing;
use App\Models\KycDocument;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

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
        $user = $request->user();

        if (!$user) {
            Log::error('Tentativo eliminazione account: utente non autenticato');
            return response()->json([
                'message' => 'Utente non autenticato'
            ], 401);
        }

        Log::info('Inizio eliminazione account per utente: ' . $user->id . ' (' . $user->email . ')');

        try {
            // 1. Elimina account Stripe Connect se presente
            if ($user->stripe_account_id) {
                try {
                    $result = $this->stripeService->deleteConnectAccount($user->stripe_account_id);
                    if (!$result['success']) {
                        Log::warning('Errore eliminazione account Stripe Connect per utente ' . $user->id . ': ' . ($result['error'] ?? 'Errore sconosciuto'));
                    }
                } catch (\Exception $e) {
                    Log::error('Eccezione durante eliminazione account Stripe Connect per utente ' . $user->id . ': ' . $e->getMessage());
                }
            }

            // 2. Elimina file fisici delle carte (immagini delle inserzioni)
            $cardListings = CardListing::where('seller_id', $user->id)->get();
            foreach ($cardListings as $listing) {
                if ($listing->images && is_array($listing->images)) {
                    foreach ($listing->images as $imagePath) {
                        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                            try {
                                Storage::disk('public')->delete($imagePath);
                            } catch (\Exception $e) {
                                Log::warning('Errore eliminazione immagine inserzione ' . $listing->id . ': ' . $e->getMessage());
                            }
                        }
                    }
                }
            }

            // 3. Elimina file fisici KYC (documenti e immagini)
            $kycDocuments = KycDocument::where('user_id', $user->id)->get();
            foreach ($kycDocuments as $document) {
                // Elimina file_path se presente (nuovo formato)
                if ($document->file_path && Storage::disk('kyc')->exists($document->file_path)) {
                    try {
                        Storage::disk('kyc')->delete($document->file_path);
                    } catch (\Exception $e) {
                        Log::warning('Errore eliminazione file KYC ' . $document->id . ': ' . $e->getMessage());
                    }
                }
                
                // Elimina front_image_path se presente (vecchio formato)
                if ($document->front_image_path && Storage::disk('public')->exists($document->front_image_path)) {
                    try {
                        Storage::disk('public')->delete($document->front_image_path);
                    } catch (\Exception $e) {
                        Log::warning('Errore eliminazione immagine fronte KYC ' . $document->id . ': ' . $e->getMessage());
                    }
                }
                
                // Elimina back_image_path se presente (vecchio formato)
                if ($document->back_image_path && Storage::disk('public')->exists($document->back_image_path)) {
                    try {
                        Storage::disk('public')->delete($document->back_image_path);
                    } catch (\Exception $e) {
                        Log::warning('Errore eliminazione immagine retro KYC ' . $document->id . ': ' . $e->getMessage());
                    }
                }
            }

            // 4. Elimina avatar se presente
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                try {
                    Storage::disk('public')->delete($user->avatar);
                } catch (\Exception $e) {
                    Log::warning('Errore eliminazione avatar utente ' . $user->id . ': ' . $e->getMessage());
                }
            }

            // 5. Elimina tutti i token
            $user->tokens()->delete();

            // 6. Elimina l'utente (cascade eliminerà automaticamente tutti i dati correlati nel database)
            // Le foreign key con onDelete('cascade') elimineranno:
            // - card_listings (seller_id)
            // - orders (buyer_id, seller_id)
            // - order_feedbacks (buyer_id, seller_id)
            // - wishlists (user_id)
            // - user_addresses (user_id)
            // - user_payment_methods (user_id)
            // - user_notifications (user_id)
            // - kyc_documents (user_id)
            // - shipping_zones (user_id)
            // - order_conversations (buyer_id, seller_id)
            // - order_messages (sender_id)
            // - availabilities (user_id)
            
            Log::info('Eliminazione utente dal database: ' . $user->id);
            $user->delete();
            Log::info('Utente eliminato con successo: ' . $user->id);

            return response()->json([
                'message' => 'Account eliminato con successo'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Errore durante eliminazione account utente ' . ($user->id ?? 'sconosciuto') . ': ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Errore durante l\'eliminazione dell\'account: ' . $e->getMessage()
            ], 500);
        }
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

    /**
     * Crea una nuova zona di spedizione per l'utente
     */
    public function createShippingZone(Request $request)
    {
        $request->validate([
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

        try {
            $zone = ShippingZone::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'country_code' => $request->country_code,
                'region' => $request->region,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'shipping_cost' => $request->shipping_cost,
                'delivery_days_min' => $request->delivery_days_min,
                'delivery_days_max' => $request->delivery_days_max,
                'is_active' => $request->get('is_active', true)
            ]);

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
     * Aggiorna una zona di spedizione dell'utente
     */
    public function updateShippingZone(Request $request, ShippingZone $shippingZone)
    {
        // Verifica che la zona appartenga all'utente
        if ($shippingZone->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accesso negato'
            ], 403);
        }

        $request->validate([
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

        try {
            $shippingZone->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Zona di spedizione aggiornata con successo',
                'data' => $shippingZone
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
     * Elimina una zona di spedizione dell'utente
     */
    public function deleteShippingZone(Request $request, ShippingZone $shippingZone)
    {
        // Verifica che la zona appartenga all'utente
        if ($shippingZone->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accesso negato'
            ], 403);
        }

        // Verifica se ci sono inserzioni che usano questa zona
        $listingsCount = $shippingZone->cardListings()->count();
        if ($listingsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossibile eliminare la zona. È utilizzata da {$listingsCount} inserzioni."
            ], 422);
        }

        try {
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

}
