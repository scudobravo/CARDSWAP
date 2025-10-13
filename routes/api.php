<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FootballFilterController;
use App\Http\Controllers\Api\BasketballFilterController;
use App\Http\Controllers\Api\PokemonFilterController;
use App\Http\Controllers\Api\CardSearchController;
use App\Http\Controllers\Api\CardModelController;
use App\Http\Controllers\Api\CardListingController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\StripeConnectController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CartController;

// Grading Companies
Route::get('/grading-companies', function () {
    try {
        $companies = DB::table('grading_companies')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
            
        return response()->json($companies);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Errore nel caricamento grading companies',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Shipping Zones
Route::get('/shipping-zones', function () {
    try {
        $zones = DB::table('shipping_zones')
            ->select('id', 'name', 'country_code', 'shipping_cost', 'delivery_days_min', 'delivery_days_max')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($zone) {
                // Aggiungi una descrizione basata sui dati della zona
                $zone->description = "Spedizione in {$zone->country_code} - €{$zone->shipping_cost}";
                return $zone;
            });
            
        return response()->json($zones);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Errore nel caricamento zone di spedizione',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Check if shipping zones exist (public endpoint)
Route::get('/shipping-zones/check', function () {
    try {
        $zonesCount = DB::table('shipping_zones')
            ->where('is_active', true)
            ->count();
            
        return response()->json([
            'has_zones' => $zonesCount > 0,
            'zones_count' => $zonesCount
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'has_zones' => false,
            'zones_count' => 0,
            'error' => $e->getMessage()
        ]);
    }
});

// Debug routes (senza autenticazione per test)
Route::get('/debug-tokens', function () {
    $users = DB::table('users')
        ->select('id', 'name', 'email', 'remember_token', 'api_token', 'kyc_status')
        ->get();
        
    return response()->json([
        'users' => $users,
        'message' => 'Usa uno di questi token con /api/debug-auth?token=TOKEN'
    ]);
});

Route::get('/debug-auth', function (Request $request) {
    $token = $request->query('token');
    
    if (!$token) {
        return response()->json([
            'error' => 'Token mancante',
            'message' => 'Aggiungi ?token=YOUR_TOKEN alla URL'
        ], 400);
    }
    
    try {
        // Verifica token
        $user = DB::table('users')
            ->where('remember_token', $token)
            ->orWhere('api_token', $token)
            ->first();
            
        if (!$user) {
            return response()->json([
                'error' => 'Token non valido',
                'message' => 'Il token fornito non è valido'
            ], 401);
        }
        
        // Carica dati utente
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'kyc_status' => $user->kyc_status,
            'created_at' => $user->created_at
        ];
        
        // Carica statistiche
        $stats = [
            'orders' => [
                'total' => DB::table('orders')->where('buyer_id', $user->id)->orWhere('seller_id', $user->id)->count(),
                'as_buyer' => DB::table('orders')->where('buyer_id', $user->id)->count(),
                'as_seller' => DB::table('orders')->where('seller_id', $user->id)->count(),
            ],
            'listings' => [
                'total' => DB::table('card_listings')->where('seller_id', $user->id)->count(),
                'active' => DB::table('card_listings')->where('seller_id', $user->id)->where('status', 'active')->count(),
            ],
            'wishlist' => [
                'total_items' => DB::table('wishlists')->where('user_id', $user->id)->count(),
            ],
            'notifications' => [
                'unread' => DB::table('user_notifications')->where('user_id', $user->id)->whereNull('read_at')->count(),
            ]
        ];
        
        return response()->json([
            'success' => true,
            'user' => $userData,
            'stats' => $stats,
            'message' => 'Debug completato con successo'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Errore interno',
            'message' => $e->getMessage()
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rotte pubbliche
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Rotte per categorie (pubbliche)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// Rotte per filtri calcio (pubbliche)
Route::prefix('football/filters')->group(function () {
    Route::get('/options', [FootballFilterController::class, 'getFilterOptions']);
    Route::get('/teams/search', [FootballFilterController::class, 'searchTeams']);
    Route::get('/teams/{id}', [FootballFilterController::class, 'getTeamById']);
    Route::get('/teams/by-league', [FootballFilterController::class, 'getTeamsByLeague']);
    Route::get('/players/search', [FootballFilterController::class, 'searchPlayers']);
    Route::get('/players/{id}', [FootballFilterController::class, 'getPlayerById']);
    Route::get('/players/by-team', [FootballFilterController::class, 'getPlayersByTeam']);
    Route::get('/players/by-league', [FootballFilterController::class, 'getPlayersByLeague']);
    Route::get('/card-sets/search', [FootballFilterController::class, 'searchCardSets']);
    Route::get('/card-sets/{id}', [FootballFilterController::class, 'getCardSetById']);
    Route::get('/card-sets/by-year', [FootballFilterController::class, 'getCardSetsByYear']);
    Route::get('/card-sets/by-brand', [FootballFilterController::class, 'getCardSetsByBrand']);
    Route::get('/years/available', [FootballFilterController::class, 'getAvailableYears']);
    Route::get('/brands/available', [FootballFilterController::class, 'getAvailableBrands']);
    Route::get('/rarities/available', [FootballFilterController::class, 'getAvailableRarities']);
    Route::get('/grading-scores/by-company', [FootballFilterController::class, 'getGradingScoresByCompany']);
            Route::get('/chained', [FootballFilterController::class, 'getChainedFilters']);
            Route::get('/advanced', [FootballFilterController::class, 'getAdvancedFilters']);
            Route::get('/products', [FootballFilterController::class, 'getFilteredProducts']);
});

// Rotte per filtri basketball (pubbliche)
Route::prefix('basketball/filters')->group(function () {
    Route::get('/options', [BasketballFilterController::class, 'getFilterOptions']);
    Route::get('/teams/search', [BasketballFilterController::class, 'searchTeams']);
    Route::get('/teams/{id}', [BasketballFilterController::class, 'getTeamById']);
    Route::get('/teams/by-league', [BasketballFilterController::class, 'getTeamsByLeague']);
    Route::get('/players/search', [BasketballFilterController::class, 'searchPlayers']);
    Route::get('/players/{id}', [BasketballFilterController::class, 'getPlayerById']);
    Route::get('/players/by-team', [BasketballFilterController::class, 'getPlayersByTeam']);
    Route::get('/players/by-league', [BasketballFilterController::class, 'getPlayersByLeague']);
    Route::get('/card-sets/search', [BasketballFilterController::class, 'searchCardSets']);
    Route::get('/card-sets/{id}', [BasketballFilterController::class, 'getCardSetById']);
    Route::get('/card-sets/by-year', [BasketballFilterController::class, 'getCardSetsByYear']);
    Route::get('/card-sets/by-brand', [BasketballFilterController::class, 'getCardSetsByBrand']);
    Route::get('/years/available', [BasketballFilterController::class, 'getAvailableYears']);
    Route::get('/brands/available', [BasketballFilterController::class, 'getAvailableBrands']);
    Route::get('/rarities/available', [BasketballFilterController::class, 'getAvailableRarities']);
    Route::get('/grading-scores/by-company', [BasketballFilterController::class, 'getGradingScoresByCompany']);
    Route::get('/chained', [BasketballFilterController::class, 'getChainedFilters']);
    Route::get('/advanced', [BasketballFilterController::class, 'getAdvancedFilters']);
    Route::get('/products', [BasketballFilterController::class, 'getFilteredProducts']);
});

// Rotte per filtri pokemon (pubbliche)
Route::prefix('pokemon/filters')->group(function () {
    Route::get('/options', [PokemonFilterController::class, 'getFilterOptions']);
    Route::get('/teams/search', [PokemonFilterController::class, 'searchTeams']);
    Route::get('/teams/{id}', [PokemonFilterController::class, 'getTeamById']);
    Route::get('/teams/by-league', [PokemonFilterController::class, 'getTeamsByLeague']);
    Route::get('/players/search', [PokemonFilterController::class, 'searchPlayers']);
    Route::get('/players/{id}', [PokemonFilterController::class, 'getPlayerById']);
    Route::get('/players/by-team', [PokemonFilterController::class, 'getPlayersByTeam']);
    Route::get('/players/by-league', [PokemonFilterController::class, 'getPlayersByLeague']);
    Route::get('/card-sets/search', [PokemonFilterController::class, 'searchCardSets']);
    Route::get('/card-sets/{id}', [PokemonFilterController::class, 'getCardSetById']);
    Route::get('/card-sets/by-year', [PokemonFilterController::class, 'getCardSetsByYear']);
    Route::get('/card-sets/by-brand', [PokemonFilterController::class, 'getCardSetsByBrand']);
    Route::get('/years/available', [PokemonFilterController::class, 'getAvailableYears']);
    Route::get('/brands/available', [PokemonFilterController::class, 'getAvailableBrands']);
    Route::get('/rarities/available', [PokemonFilterController::class, 'getAvailableRarities']);
    Route::get('/grading-scores/by-company', [PokemonFilterController::class, 'getGradingScoresByCompany']);
    Route::get('/chained', [PokemonFilterController::class, 'getChainedFilters']);
    Route::get('/advanced', [PokemonFilterController::class, 'getAdvancedFilters']);
    Route::get('/products', [PokemonFilterController::class, 'getFilteredProducts']);
});

// Rotte per ricerca carte (pubbliche)
Route::prefix('cards')->group(function () {
    Route::get('/search', [CardSearchController::class, 'search']);
    Route::get('/{card}', [CardSearchController::class, 'show']);
});

// Rotte per modelli di carte (pubbliche)
Route::prefix('card-models')->group(function () {
    Route::get('/', [CardModelController::class, 'index']);
    Route::get('/search', [CardModelController::class, 'search']);
    Route::get('/filters', [CardModelController::class, 'getFilterOptions']);
    Route::get('/{cardModel}', [CardModelController::class, 'show']);
});


// Rotte per inserzioni (pubbliche per visualizzazione)
Route::prefix('listings')->group(function () {
    Route::get('/', [CardListingController::class, 'index']);
    Route::get('/search', [CardListingController::class, 'search']);
    Route::get('/{cardListing}', [CardListingController::class, 'show']);
});

// Rotte per i modelli di carte (pubbliche per ricerca)
Route::prefix('card-models')->group(function () {
    Route::post('/search', [CardModelController::class, 'search']);
    Route::get('/chained-filters', [CardModelController::class, 'getChainedFilters']);
    Route::get('/{cardModel}', [CardModelController::class, 'show']);
});

// Rotte per disponibilità (pubbliche per verifica)
Route::prefix('availability')->group(function () {
    Route::post('/check-single', [AvailabilityController::class, 'checkSingle']);
    Route::post('/check-multiple', [AvailabilityController::class, 'checkMultiple']);
    
        // Rotte protette per gestione disponibilità
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/lock', [AvailabilityController::class, 'lock']);
            Route::post('/release', [AvailabilityController::class, 'release']);
            Route::post('/confirm', [AvailabilityController::class, 'confirm']);
        });
    
    // Rotte admin per pulizia
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/clean-expired', [AvailabilityController::class, 'cleanExpired']);
    });
});

// Rotte per gestione immagini (pubbliche per visualizzazione)
Route::prefix('images')->group(function () {
    Route::get('/info', [ImageController::class, 'info']);
});

// Rotte per carrello (pubbliche per permettere carrello anche a utenti non autenticati)
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/add', [CartController::class, 'addItem']);
    Route::put('/update-quantity', [CartController::class, 'updateQuantity']);
    Route::delete('/remove', [CartController::class, 'removeItem']);
    Route::post('/shipping-costs', [CartController::class, 'getShippingCosts']);
    Route::post('/validate', [CartController::class, 'validate']);
    Route::delete('/clear', [CartController::class, 'clear']);
});

// Rotte per homepage e navigazione (pubbliche)
Route::prefix('home')->group(function () {
    Route::get('/', [HomeController::class, 'index']);
    Route::get('/navigation', [HomeController::class, 'navigation']);
    Route::get('/search-suggestions', [HomeController::class, 'searchSuggestions']);
    Route::get('/trending', [HomeController::class, 'trending']);
});

// Rotte per categorie dinamiche (pubbliche)
Route::prefix('category')->group(function () {
    Route::get('/cards', [CardController::class, 'getCardsByCategory']);
    Route::get('/categories', [CardController::class, 'getCategories']);
});

// Rotte protette
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    
    // Rotte per modelli di carte (protette per amministratori)
    Route::prefix('card-models')->group(function () {
        Route::post('/', [CardModelController::class, 'store']);
        Route::put('/{cardModel}', [CardModelController::class, 'update']);
        Route::delete('/{cardModel}', [CardModelController::class, 'destroy']);
    });
    
    // Rotte per inserzioni (protette per venditori - richiedono KYC)
    Route::prefix('listings')->middleware('require.kyc')->group(function () {
        Route::post('/', [CardListingController::class, 'store']);
        Route::post('/bulk', [CardListingController::class, 'storeBulk']);
        Route::put('/{cardListing}', [CardListingController::class, 'update']);
        Route::delete('/{cardListing}', [CardListingController::class, 'destroy']);
        Route::get('/my/listings', [CardListingController::class, 'myListings']);
        Route::post('/{cardListing}/duplicate', [CardListingController::class, 'duplicate']);
        Route::patch('/{cardListing}/status', [CardListingController::class, 'changeStatus']);
        
        // Funzionalità aggiuntive per venditori
        Route::get('/my/stats', [CardListingController::class, 'getStats']);
        Route::get('/my/analytics', [CardListingController::class, 'getAnalytics']);
        Route::get('/my/export', [CardListingController::class, 'export']);
        Route::patch('/bulk/status', [CardListingController::class, 'bulkUpdateStatus']);
        Route::delete('/bulk/delete', [CardListingController::class, 'bulkDelete']);
    });
    
    // Rotte per gestione immagini (protette per utenti autenticati)
    Route::prefix('images')->group(function () {
        Route::post('/upload', [ImageController::class, 'upload']);
        Route::post('/upload-multiple', [ImageController::class, 'uploadMultiple']);
        Route::delete('/delete', [ImageController::class, 'delete']);
        Route::post('/optimize', [ImageController::class, 'optimize']);
    });
    
    // Rotte per wishlist (protette per utenti autenticati)
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/', [WishlistController::class, 'store']);
        Route::put('/{wishlist}', [WishlistController::class, 'update']);
        Route::delete('/{wishlist}', [WishlistController::class, 'destroy']);
        Route::delete('/card/{cardModelId}', [WishlistController::class, 'destroyByCardModel']);
        Route::delete('/clear', [WishlistController::class, 'clear']);
        Route::post('/multiple', [WishlistController::class, 'addMultiple']);
        Route::get('/statistics', [WishlistController::class, 'statistics']);
        Route::get('/search', [WishlistController::class, 'search']);
        Route::get('/export', [WishlistController::class, 'export']);
    });


    // Rotte per gestione utenti
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'updatePassword']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
        Route::delete('/avatar', [UserController::class, 'removeAvatar']);
        Route::put('/business', [UserController::class, 'updateBusinessInfo']);
        Route::get('/stats', [UserController::class, 'stats']);
        Route::get('/activity', [UserController::class, 'activity']);
        Route::delete('/account', [UserController::class, 'deleteAccount']);
        Route::get('/notification-preferences', [UserController::class, 'getNotificationPreferences']);
        Route::put('/notification-preferences', [UserController::class, 'updateNotificationPreferences']);
    });

    // Rotte per indirizzi utente
    Route::prefix('addresses')->group(function () {
        Route::get('/', [UserAddressController::class, 'index']);
        Route::post('/', [UserAddressController::class, 'store']);
        Route::get('/{address}', [UserAddressController::class, 'show']);
        Route::put('/{address}', [UserAddressController::class, 'update']);
        Route::delete('/{address}', [UserAddressController::class, 'destroy']);
        Route::patch('/{address}/set-default', [UserAddressController::class, 'setDefault']);
    });

    // Rotte per KYC (solo Stripe Identity)
    Route::prefix('kyc')->group(function () {
        Route::get('/status', [KycController::class, 'status']);
        Route::post('/start', [KycController::class, 'startKyc']);
        Route::get('/check-status', [KycController::class, 'checkKycStatus']);
        Route::post('/complete-profile', [KycController::class, 'completeProfile']);
    });

    // Rotte per Stripe Connect
    Route::prefix('stripe')->group(function () {
        Route::post('/account/create', [StripeConnectController::class, 'createAccount']);
        Route::post('/account/onboarding', [StripeConnectController::class, 'createOnboardingLink']);
        Route::post('/account/login', [StripeConnectController::class, 'createLoginLink']);
        Route::get('/account/status', [StripeConnectController::class, 'getAccountStatus']);
        Route::get('/account/can-receive-payments', [StripeConnectController::class, 'canReceivePayments']);
        Route::get('/dashboard', [StripeConnectController::class, 'getSellerDashboard']);
        Route::put('/account/update', [StripeConnectController::class, 'updateAccount']);
        Route::delete('/account', [StripeConnectController::class, 'deleteAccount']);
    });

    // Rotte per pagamenti (richiedono KYC)
    Route::prefix('payments')->middleware('require.kyc')->group(function () {
        Route::post('/confirm-order', [PaymentController::class, 'confirmOrder']);
        Route::post('/create', [PaymentController::class, 'createPayment']);
        Route::post('/confirm', [PaymentController::class, 'confirmPayment']);
        Route::post('/refund', [PaymentController::class, 'createRefund']);
    });

    // Rotte per ordini
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/statistics', [OrderController::class, 'statistics']);
        Route::get('/seller', [OrderController::class, 'getSellerOrders']);
        Route::get('/seller/statistics', [OrderController::class, 'getSellerStatistics']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
        // Tracking
        Route::get('/{id}/tracking', [TrackingController::class, 'history']);
        Route::post('/{id}/tracking/events', [TrackingController::class, 'addEvent']);
    });

    // Rotte per statistiche vendite
    Route::prefix('sales')->group(function () {
        Route::get('/statistics', [App\Http\Controllers\Api\SalesStatisticsController::class, 'getSalesStatistics']);
        Route::get('/feedback', [App\Http\Controllers\Api\SalesFeedbackController::class, 'getSellerFeedbacks']);
        Route::get('/feedback/statistics', [App\Http\Controllers\Api\SalesFeedbackController::class, 'getFeedbackStatistics']);
        Route::post('/feedback/{feedback}/response', [App\Http\Controllers\Api\SalesFeedbackController::class, 'respondToFeedback']);
    });

    // Conversazioni e messaggi ordine (non realtime)
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index']);
        Route::post('/start', [ConversationController::class, 'start']);
        Route::get('/{conversation}/messages', [ConversationController::class, 'messages']);
        Route::post('/{conversation}/messages', [ConversationController::class, 'sendMessage']);
        Route::patch('/{conversation}/read', [ConversationController::class, 'markRead']);
    });

    // Sistema feedback
    Route::prefix('feedback')->group(function () {
        Route::get('/', [FeedbackController::class, 'index']); // Lista feedback per venditore
        Route::post('/', [FeedbackController::class, 'store']); // Lascia feedback
        Route::get('/{feedback}', [FeedbackController::class, 'show']); // Mostra feedback specifico
        Route::put('/{feedback}', [FeedbackController::class, 'update']); // Aggiorna feedback
    });

    // Dashboard utente
    Route::get('/dashboard', [UserController::class, 'dashboard']); // Dashboard completa utente

    // Pannello amministrazione (solo admin)
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']); // Statistiche dashboard
        Route::get('/users', [AdminController::class, 'users']); // Lista utenti
        Route::get('/users/{user}', [AdminController::class, 'user']); // Dettaglio utente
        Route::put('/users/{user}/status', [AdminController::class, 'updateUserStatus']); // Aggiorna stato utente
        Route::get('/orders', [AdminController::class, 'orders']); // Lista ordini
        Route::get('/orders/{order}', [AdminController::class, 'order']); // Dettaglio ordine
        Route::get('/feedbacks', [AdminController::class, 'feedbacks']); // Lista feedback per moderazione
        Route::put('/feedbacks/{feedback}/moderate', [AdminController::class, 'moderateFeedback']); // Moderazione feedback
        
        // Gestione KYC
        Route::get('/kyc/pending', [KycController::class, 'pendingDocuments']); // Documenti KYC in attesa
        Route::get('/kyc/users/{user}/documents', [KycController::class, 'userDocuments']); // Documenti utente
        Route::get('/kyc/documents/{document}/view', [KycController::class, 'viewDocument']); // Visualizza documento
        Route::get('/kyc/documents/{document}/download', [KycController::class, 'downloadDocument']); // Download documento
        Route::post('/kyc/users/{user}/approve', [KycController::class, 'approveKyc']); // Approva KYC
        Route::post('/kyc/users/{user}/reject', [KycController::class, 'rejectKyc']); // Rifiuta KYC
        
        // Gestione Zone di Spedizione
        Route::get('/shipping-zones', [AdminController::class, 'shippingZones']); // Lista zone
        Route::post('/shipping-zones', [AdminController::class, 'createShippingZone']); // Crea zona
        Route::put('/shipping-zones/{shippingZone}', [AdminController::class, 'updateShippingZone']); // Aggiorna zona
        Route::delete('/shipping-zones/{shippingZone}', [AdminController::class, 'deleteShippingZone']); // Elimina zona
        Route::get('/shipping-zones/check', [AdminController::class, 'checkShippingZones']); // Verifica esistenza zone
    });

    // KYC per utenti
    Route::prefix('kyc')->middleware('auth:sanctum')->group(function () {
        Route::post('/upload', [KycController::class, 'uploadDocument']); // Upload documento
        Route::post('/validate-fiscal-code', [KycController::class, 'validateFiscalCode']); // Valida CF
    });
});

// Rotte webhook Stripe (non protette)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Rotte webhook test (solo in sviluppo)
if (config('app.env') === 'local') {
    Route::post('/stripe/webhook/test', [StripeWebhookController::class, 'test']);
}

// Public API routes for category pages
Route::get('/category/categories', [App\Http\Controllers\Api\CardController::class, 'getCategories']);
Route::get('/category/cards', [App\Http\Controllers\Api\CardController::class, 'getCardsByCategory']);
Route::get('/card/{id}', [App\Http\Controllers\Api\CardController::class, 'getCardDetails']);
Route::get('/card/{category}/{slug}', [App\Http\Controllers\Api\CardController::class, 'getCardDetailsBySlug']);
Route::get('/card/{id}/related', [App\Http\Controllers\Api\CardController::class, 'getRelatedProducts']);
Route::get('/card/{category}/{slug}/related', [App\Http\Controllers\Api\CardController::class, 'getRelatedProductsBySlug']);

// Report API routes
Route::post('/reports', [App\Http\Controllers\Api\ReportController::class, 'submitReport']);

// Rotte per controllo disponibilità (pubbliche)
Route::prefix('availability')->group(function () {
    Route::post('/check-single', [AvailabilityController::class, 'checkSingle']);
    Route::post('/check-multiple', [AvailabilityController::class, 'checkMultiple']);
    Route::post('/reserve', [AvailabilityController::class, 'reserve']);
    Route::post('/release', [AvailabilityController::class, 'release']);
    Route::post('/confirm', [AvailabilityController::class, 'confirm']);
    Route::post('/clean-expired', [AvailabilityController::class, 'cleanExpired']);
});

// Rotte per checkout (protette)
Route::middleware('auth:sanctum')->prefix('checkout')->group(function () {
    Route::post('/create-order', [CheckoutController::class, 'createOrder']);
    Route::get('/order-status', [CheckoutController::class, 'getOrderStatus']);
});

// Rotte per indirizzi utente (protette)
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/addresses', [UserAddressController::class, 'index']);
    Route::post('/addresses', [UserAddressController::class, 'store']);
    Route::put('/addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('/addresses/{id}', [UserAddressController::class, 'destroy']);
    Route::post('/addresses/{id}/set-default', [UserAddressController::class, 'setDefault']);
});


// Shippo shipping routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/shipping/calculate-rates', [App\Http\Controllers\Api\ShippoController::class, 'calculateRates']);
    Route::post('/shipping/purchase-label', [App\Http\Controllers\Api\ShippoController::class, 'purchaseLabel']);
    Route::get('/shipping/tracking', [App\Http\Controllers\Api\ShippoController::class, 'getTracking']);
    Route::post('/shipping/validate-address', [App\Http\Controllers\Api\ShippoController::class, 'validateAddress']);
    Route::post('/shipping/schedule-pickup', [App\Http\Controllers\Api\ShippoController::class, 'schedulePickup']);
    Route::post('/shipping/refund-label', [App\Http\Controllers\Api\ShippoController::class, 'refundLabel']);
    Route::get('/shipping/carrier-accounts', [App\Http\Controllers\Api\ShippoController::class, 'getCarrierAccounts']);
});

// Shippo webhook (no auth required)
Route::post('/webhooks/shippo', [App\Http\Controllers\Api\ShippoWebhookController::class, 'handleWebhook']);

// Contact form (no auth required)
Route::post('/contact', [App\Http\Controllers\Api\ContactController::class, 'sendMessage']);
