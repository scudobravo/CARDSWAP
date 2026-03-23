<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FootballFilterController;
use App\Http\Controllers\Api\BasketballFilterController;
use App\Http\Controllers\Api\PokemonFilterController;
use App\Http\Controllers\Api\DisneyFilterController;
use App\Http\Controllers\Api\SpongebobFilterController;
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
use App\Http\Controllers\Api\AfterShipWebhookController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ShippingZoneController;
use App\Http\Controllers\Api\Seller\ShippingPriceTableController;
use App\Http\Controllers\Api\Seller\SellerOrderController;
use App\Http\Controllers\Api\Shipping\CardSwapShippingController;
use App\Http\Controllers\Api\UserNotificationController;
use App\Http\Controllers\Api\MiscController;

// Grading Companies (controller per consentire route:cache)
Route::get('/grading-companies', [MiscController::class, 'gradingCompanies']);

// Shipping Zones - API pubbliche (NON più per calcolo prezzi - legacy pricing rimosso)
// NOTA: Endpoint pricing legacy rimossi:
// - /shipping-zones/calculate-price - RIMOSSO
// - /shipping-zones/calculate-multiple-prices - RIMOSSO
// - /shipping-zones/calculate-country-prices - RIMOSSO
// Usa invece POST /api/shipping/v1/calculate-rates per CardSwap Shipping V1.
Route::post('/shipping-zones/check-country-support', [ShippingZoneController::class, 'checkCountrySupport']);
Route::get('/shipping-zones/available-carriers', [ShippingZoneController::class, 'getAvailableCarriers']);

// Shipping Zones - Gestione zone (richiede autenticazione)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/shipping-zones', [ShippingZoneController::class, 'index']); // Zone dell'utente autenticato
    Route::post('/shipping-zones', [ShippingZoneController::class, 'store']);
    Route::put('/shipping-zones/{id}', [ShippingZoneController::class, 'update']);
    Route::delete('/shipping-zones/{id}', [ShippingZoneController::class, 'destroy']);
});

// Shipping Zones - Zone dell'utente autenticato (legacy, controller per route:cache)
Route::middleware('auth:sanctum')->get('/user/shipping-zones', [MiscController::class, 'userShippingZones']);

// Check if shipping zones exist for authenticated user
Route::get('/shipping-zones/check', [MiscController::class, 'shippingZonesCheck'])->middleware('auth:sanctum');

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

Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'user']);

// Rotte pubbliche
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:auth');
Route::post('/auth/resend-verification', [AuthController::class, 'resendVerificationEmail'])->middleware('throttle:auth');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');

Route::middleware('throttle:public')->group(function () {
// Rotte per top players/pokemon (pubbliche) - devono essere prima per evitare conflitti
Route::get('/top/football/{playerName}', [FootballFilterController::class, 'getListingsByPlayerName']);
Route::get('/top/basketball/{playerName}', [BasketballFilterController::class, 'getListingsByPlayerName']);
Route::get('/top/pokemon/{pokemonName}', [PokemonFilterController::class, 'getListingsByPlayerName']);

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
    Route::get('/rarities/search', [FootballFilterController::class, 'searchRarities']);
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
    Route::get('/rarities/search', [BasketballFilterController::class, 'searchRarities']);
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
    Route::get('/rarities/search', [PokemonFilterController::class, 'searchRarities']);
    Route::get('/grading-scores/by-company', [PokemonFilterController::class, 'getGradingScoresByCompany']);
    Route::get('/chained', [PokemonFilterController::class, 'getChainedFilters']);
    Route::get('/advanced', [PokemonFilterController::class, 'getAdvancedFilters']);
    Route::get('/products', [PokemonFilterController::class, 'getFilteredProducts']);
});

// Rotte per filtri Disney (pubbliche)
Route::prefix('disney/filters')->group(function () {
    Route::get('/options', [DisneyFilterController::class, 'getFilterOptions']);
    Route::get('/card-sets/search', [DisneyFilterController::class, 'searchCardSets']);
    Route::get('/rarities/search', [DisneyFilterController::class, 'searchRarities']);
    Route::get('/products', [DisneyFilterController::class, 'getFilteredProducts']);
});

// Rotte per filtri Spongebob (pubbliche)
Route::prefix('spongebob/filters')->group(function () {
    Route::get('/options', [SpongebobFilterController::class, 'getFilterOptions']);
    Route::get('/card-sets/search', [SpongebobFilterController::class, 'searchCardSets']);
    Route::get('/rarities/search', [SpongebobFilterController::class, 'searchRarities']);
    Route::get('/products', [SpongebobFilterController::class, 'getFilteredProducts']);
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
    Route::get('/{cardListing}/seller-stats', [CardListingController::class, 'sellerStatsByListing']);
    Route::get('/{cardListing}', [CardListingController::class, 'show']);
    Route::get('/{cardListing}/price-history', [CardListingController::class, 'getPriceHistory']);
    Route::get('/{cardListing}/related', [CardListingController::class, 'getRelatedListings']);
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
Route::prefix('images')->middleware(['auth:sanctum'])->group(function () {
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

// Statistiche venditore (pubbliche – per numero vendite/rating sulla pagina carta)
Route::get('/sellers/{sellerId}/stats', [FeedbackController::class, 'sellerStats']);
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

    // Notifiche in-app (FASE D3)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [UserNotificationController::class, 'index']);
        Route::get('/unread-count', [UserNotificationController::class, 'unreadCount']);
        Route::post('/read-all', [UserNotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/read', [UserNotificationController::class, 'markAsRead']);
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
        Route::delete('/cancel', [KycController::class, 'cancelVerification']); // Cancella sessione di verifica fallita o in corso
    });

    // Rotte per Stripe Connect
    Route::prefix('stripe')->group(function () {
        Route::get('/check-setup', [StripeConnectController::class, 'checkConnectSetup']); // Verifica configurazione Connect
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
        Route::post('/{id}/confirm-payment', [OrderController::class, 'confirmPayment']);
        Route::post('/{id}/dispute', [OrderController::class, 'openDispute']); // Apri dispute (solo buyer)
        // Tracking
        Route::get('/{id}/tracking', [TrackingController::class, 'history']);
        Route::post('/{id}/tracking/events', [TrackingController::class, 'addEvent']);
    });

    // CardSwap Shipping V1 – Dettaglio ordine venditore (FASE D1)
    Route::prefix('seller/orders')->group(function () {
        Route::get('/{orderId}', [SellerOrderController::class, 'show']);
        Route::post('/{orderId}/tracking', [SellerOrderController::class, 'addTracking']);
        Route::patch('/{orderId}/tracking', [SellerOrderController::class, 'updateTracking']);
        Route::post('/{orderId}/mark-shipped', [SellerOrderController::class, 'markShipped']);
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
        Route::post('/start-for-listing', [ConversationController::class, 'startForListing']);
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
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']); // Statistiche dashboard
        Route::get('/users', [AdminController::class, 'users']); // Lista utenti
        Route::get('/users/{user}', [AdminController::class, 'user']); // Dettaglio utente
        Route::put('/users/{user}/status', [AdminController::class, 'updateUserStatus']); // Aggiorna stato utente
        Route::get('/orders', [AdminController::class, 'orders']); // Lista ordini
        Route::get('/orders/{order}', [AdminController::class, 'order']); // Dettaglio ordine
        Route::get('/feedbacks', [AdminController::class, 'feedbacks']); // Lista feedback per moderazione
        Route::put('/feedbacks/{feedback}/moderate', [AdminController::class, 'moderateFeedback']); // Moderazione feedback
        Route::get('/listings', [AdminController::class, 'listings']);
        Route::get('/listings/{cardListing}', [AdminController::class, 'listing']);
        Route::patch('/listings/{cardListing}', [AdminController::class, 'updateListing']);
        
        // Gestione KYC
        Route::get('/kyc/pending', [KycController::class, 'pendingDocuments']); // Documenti KYC in attesa
        Route::get('/kyc/users/{user}/documents', [KycController::class, 'userDocuments']); // Documenti utente
        Route::get('/kyc/documents/{document}/view', [KycController::class, 'viewDocument']); // Visualizza documento
        Route::get('/kyc/documents/{document}/download', [KycController::class, 'downloadDocument']); // Download documento
        Route::post('/kyc/users/{user}/approve', [KycController::class, 'approveKyc']); // Approva KYC
        Route::post('/kyc/users/{user}/reject', [KycController::class, 'rejectKyc']); // Rifiuta KYC
        
        // Gestione Zone di Spedizione - Rimosso (ora gestito per utente)
    });

    // Gestione Zone di Spedizione per utenti - RIMOSSO (ora gestito dalle route pubbliche)
    // Route::prefix('shipping-zones')->middleware('auth:sanctum')->group(function () {
    //     Route::post('/', [UserController::class, 'createShippingZone']); // Crea zona
    //     Route::put('/{shippingZone}', [UserController::class, 'updateShippingZone']); // Aggiorna zona
    //     Route::delete('/{shippingZone}', [UserController::class, 'deleteShippingZone']); // Elimina zona
    // });

    // KYC per utenti
    Route::prefix('kyc')->middleware('auth:sanctum')->group(function () {
        Route::post('/upload', [KycController::class, 'uploadDocument']); // Upload documento
        Route::post('/validate-fiscal-code', [KycController::class, 'validateFiscalCode']); // Valida CF
    });
});

// Rotte webhook Stripe (non protette)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->middleware('throttle:public');

// Webhook AfterShip Tracking (CardSwap V1 - unica fonte aggiornamenti tracking)
// URL da configurare in AfterShip: POST /api/webhooks/aftership
// Verifica firma: header aftership-hmac-sha256 con AFTERSHIP_WEBHOOK_SECRET
Route::post('/webhooks/aftership', [AfterShipWebhookController::class, 'handle'])->middleware('throttle:public');

// Rotte webhook test (solo in sviluppo)
if (config('app.env') === 'local') {
    Route::post('/stripe/webhook/test', [StripeWebhookController::class, 'test']);
}

// Public API routes for category pages
Route::get('/category/categories', [App\Http\Controllers\Api\CardController::class, 'getCategories'])->middleware('throttle:public');
Route::get('/category/cards', [App\Http\Controllers\Api\CardController::class, 'getCardsByCategory'])->middleware('throttle:public');
Route::get('/card/{id}', [App\Http\Controllers\Api\CardController::class, 'getCardDetails'])->middleware('throttle:public');
Route::get('/card/{category}/{slug}', [App\Http\Controllers\Api\CardController::class, 'getCardDetailsBySlug'])->middleware('throttle:public');
Route::get('/card/{id}/related', [App\Http\Controllers\Api\CardController::class, 'getRelatedProducts'])->middleware('throttle:public');
Route::get('/card/{category}/{slug}/related', [App\Http\Controllers\Api\CardController::class, 'getRelatedProductsBySlug'])->middleware('throttle:public');

// Report API routes
Route::post('/reports', [App\Http\Controllers\Api\ReportController::class, 'submitReport'])->middleware('throttle:public');

// Rotte per controllo disponibilità (pubbliche)
Route::prefix('availability')->group(function () {
    Route::post('/check-single', [AvailabilityController::class, 'checkSingle'])->middleware('throttle:public');
    Route::post('/check-multiple', [AvailabilityController::class, 'checkMultiple'])->middleware('throttle:public');
    Route::post('/reserve', [AvailabilityController::class, 'reserve'])->middleware('throttle:public');
    Route::post('/release', [AvailabilityController::class, 'release'])->middleware('throttle:public');
    Route::post('/confirm', [AvailabilityController::class, 'confirm'])->middleware('throttle:public');
    Route::post('/clean-expired', [AvailabilityController::class, 'cleanExpired'])->middleware('throttle:public');
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


// ============================================
// SHIPPO ROUTES - DEPRECATE
// ============================================
// ATTENZIONE: Shippo è DEPRECATO e NON fa parte di CardSwap Shipping V1.
// 
// Shippo NON viene più utilizzato per:
// - Pricing (usa CardSwap Shipping V1: POST /api/shipping/v1/calculate-rates)
// - Checkout (usa PaymentController con shipping_selections)
// - Tracking (usa AfterShip - vedi TrackingController)
// - Post-ordine (usa AfterShip webhook)
// 
// Tutti gli endpoint Shippo sono deprecati e NON fanno parte del flusso CardSwap V1.
// 
// Messaggio standard: "Shippo is deprecated and not used by CardSwap Shipping V1"
// ============================================

// Shippo endpoints (DEPRECATI - NON usati da CardSwap V1)
Route::middleware(['auth:sanctum', 'log.shippo.deprecated'])->group(function () {
    Route::post('/shipping/purchase-label', [App\Http\Controllers\Api\ShippoController::class, 'purchaseLabel']);
    Route::get('/shipping/tracking', [App\Http\Controllers\Api\ShippoController::class, 'getTracking']);
    Route::post('/shipping/validate-address', [App\Http\Controllers\Api\ShippoController::class, 'validateAddress']);
    Route::post('/shipping/schedule-pickup', [App\Http\Controllers\Api\ShippoController::class, 'schedulePickup']);
    Route::post('/shipping/refund-label', [App\Http\Controllers\Api\ShippoController::class, 'refundLabel']);
    Route::get('/shipping/carrier-accounts', [App\Http\Controllers\Api\ShippoController::class, 'getCarrierAccounts']);
});

// Shippo webhook (DISABILITATO - CardSwap V1 usa AfterShip)
Route::post('/webhooks/shippo', [App\Http\Controllers\Api\ShippoWebhookController::class, 'handleWebhook'])
    ->middleware('log.shippo.deprecated:error');

// Contact form (no auth required)
Route::post('/contact', [App\Http\Controllers\Api\ContactController::class, 'sendMessage'])->middleware('throttle:public');

// ============================================
// CardSwap Spedizioni V1 - Nuovo Sistema Pricing
// ============================================

// Calcolo tariffe per checkout (pubblico, ma può essere protetto se necessario)
Route::post('/shipping/v1/calculate-rates', [CardSwapShippingController::class, 'calculateRates'])->middleware('throttle:public');

// Gestione tabelle prezzi venditore (protette, solo seller autenticati)
Route::middleware('auth:sanctum')->prefix('seller/shipping/price-tables')->group(function () {
    Route::get('/', [ShippingPriceTableController::class, 'index']);
    Route::post('/', [ShippingPriceTableController::class, 'store']);
    Route::put('/{id}', [ShippingPriceTableController::class, 'update']);
    Route::delete('/{id}', [ShippingPriceTableController::class, 'destroy']);
    Route::post('/{id}/countries', [ShippingPriceTableController::class, 'addCountries']);
    Route::put('/{id}/countries', [ShippingPriceTableController::class, 'syncCountries']);
    Route::post('/{id}/rates', [ShippingPriceTableController::class, 'saveRates']);
    Route::post('/{id}/insured', [ShippingPriceTableController::class, 'configureInsurance']);
});
