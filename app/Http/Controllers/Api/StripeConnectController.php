<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\Request;

class StripeConnectController extends Controller
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Crea account Stripe Connect per venditore
     */
    public function createAccount(Request $request)
    {
        $user = $request->user();

        // Permetti a tutti di creare account Stripe Connect
        // Un utente può voler vendere anche se si è registrato come buyer
        // Stripe Connect può essere configurato in anticipo

        // Verifica se l'utente ha già un account
        if ($user->stripe_account_id) {
            return response()->json([
                'message' => 'Hai già un account Stripe Connect',
                'account_id' => $user->stripe_account_id
            ], 400);
        }

        // Crea account Stripe Connect
        $result = $this->stripeService->createConnectAccount($user);

        if (!$result['success']) {
            return response()->json([
                'message' => 'Errore nella creazione dell\'account Stripe Connect',
                'error' => $result['error']
            ], 500);
        }

        // Salva account ID nell'utente
        $user->update([
            'stripe_account_id' => $result['account_id']
        ]);

        return response()->json([
            'message' => 'Account Stripe Connect creato con successo',
            'account_id' => $result['account_id'],
            'account' => $result['account']
        ]);
    }

    /**
     * Crea link di onboarding per account Connect
     */
    public function createOnboardingLink(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'message' => 'Account Stripe Connect non trovato'
            ], 404);
        }

        // In locale, usa HTTP. In produzione, Stripe richiede HTTPS per live mode
        $baseUrl = config('app.url');
        
        // Se siamo in test mode e in locale, possiamo usare HTTP
        // Altrimenti usa HTTPS (richiesto per live mode)
        if (config('app.env') === 'local' && str_starts_with($baseUrl, 'http://')) {
            // In locale con test mode, HTTP va bene
            $returnUrl = $baseUrl . '/dashboard/stripe/return';
            $refreshUrl = $baseUrl . '/dashboard/stripe/refresh';
        } else {
            // In produzione o se configurato HTTPS, usa HTTPS
            $returnUrl = str_replace('http://', 'https://', $baseUrl) . '/dashboard/stripe/return';
            $refreshUrl = str_replace('http://', 'https://', $baseUrl) . '/dashboard/stripe/refresh';
        }

        $result = $this->stripeService->createAccountLink(
            $user->stripe_account_id,
            $returnUrl,
            $refreshUrl
        );

        if (!$result['success']) {
            return response()->json([
                'message' => 'Errore nella creazione del link di onboarding',
                'error' => $result['error']
            ], 500);
        }

        return response()->json([
            'message' => 'Link di onboarding creato con successo',
            'onboarding_url' => $result['url']
        ]);
    }

    /**
     * Crea link di login per account Connect
     */
    public function createLoginLink(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'message' => 'Account Stripe Connect non trovato'
            ], 404);
        }

        $result = $this->stripeService->createLoginLink($user->stripe_account_id);

        if (!$result['success']) {
            return response()->json([
                'message' => 'Errore nella creazione del link di login',
                'error' => $result['error']
            ], 500);
        }

        return response()->json([
            'message' => 'Link di login creato con successo',
            'login_url' => $result['url']
        ]);
    }

    /**
     * Ottieni stato account Connect
     */
    public function getAccountStatus(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'message' => 'Account Stripe Connect non trovato'
            ], 404);
        }

        $result = $this->stripeService->getConnectAccount($user->stripe_account_id);

        if (!$result['success']) {
            return response()->json([
                'message' => 'Errore nel recupero dello stato dell\'account',
                'error' => $result['error']
            ], 500);
        }

        // Aggiorna stato locale
        $user->updateStripeAccountStatus([
            'charges_enabled' => $result['charges_enabled'],
            'payouts_enabled' => $result['payouts_enabled'],
            'details_submitted' => $result['details_submitted'],
        ]);
        $user->refresh();

        return response()->json([
            'account_id' => $user->stripe_account_id,
            'charges_enabled' => $result['charges_enabled'],
            'payouts_enabled' => $result['payouts_enabled'],
            'details_submitted' => $result['details_submitted'],
            'role' => $user->role,
            'can_sell' => $user->canSellWithStripe(),
            'account' => $result['account']
        ]);
    }

    /**
     * Verifica configurazione Stripe Connect del marketplace
     */
    public function checkConnectSetup(Request $request)
    {
        try {
            $stripeSecret = config('services.stripe.secret');
            
            // Verifica tipo di chiave
            $isTestMode = str_starts_with($stripeSecret, 'sk_test_');
            $isLiveMode = str_starts_with($stripeSecret, 'sk_live_');
            
            // Prova a creare un account di test per verificare se Connect è abilitato
            $stripeService = app(StripeService::class);
            $testUser = new User();
            $testUser->email = 'test@example.com';
            $testUser->name = 'Test User';
            
            // Non creiamo realmente l'account, solo verifichiamo se possiamo
            $canCreateAccounts = false;
            $errorMessage = null;
            
            try {
                // Prova a recuperare l'account principale per verificare se Connect è abilitato
                $stripe = new \Stripe\StripeClient($stripeSecret);
                $account = $stripe->accounts->retrieve();
                
                // Se riusciamo a recuperare l'account, Connect potrebbe essere abilitato
                // Ma non possiamo saperlo con certezza senza provare a creare un account
                $canCreateAccounts = true;
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
            }
            
            return response()->json([
                'connect_configured' => $canCreateAccounts,
                'test_mode' => $isTestMode,
                'live_mode' => $isLiveMode,
                'environment' => config('app.env'),
                'error' => $errorMessage,
                'message' => $isLiveMode && config('app.env') === 'local' 
                    ? 'ATTENZIONE: Stai usando chiavi LIVE in locale. Usa chiavi TEST (sk_test_...)'
                    : ($canCreateAccounts ? 'Stripe Connect sembra configurato' : 'Verifica la configurazione di Stripe Connect')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'connect_configured' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica se l'utente può ricevere pagamenti
     */
    public function canReceivePayments(Request $request)
    {
        $user = $request->user();

        // Non richiediamo più che l'utente sia seller
        // Chiunque può configurare Stripe Connect per vendere in futuro

        if (!$user->stripe_account_id) {
            return response()->json([
                'can_receive_payments' => false,
                'reason' => 'Account Stripe Connect non creato'
            ]);
        }

        if (!$user->stripe_charges_enabled || !$user->stripe_payouts_enabled) {
            return response()->json([
                'can_receive_payments' => false,
                'reason' => 'Account Stripe Connect non completamente configurato',
                'charges_enabled' => $user->stripe_charges_enabled,
                'payouts_enabled' => $user->stripe_payouts_enabled
            ]);
        }

        return response()->json([
            'can_receive_payments' => true,
            'account_id' => $user->stripe_account_id,
            'charges_enabled' => $user->stripe_charges_enabled,
            'payouts_enabled' => $user->stripe_payouts_enabled
        ]);
    }

    /**
     * Ottieni dashboard venditore
     */
    public function getSellerDashboard(Request $request)
    {
        $user = $request->user();

        // Permetti a tutti di vedere la dashboard Stripe Connect
        // Non serve essere seller per configurare i pagamenti

        $dashboard = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'stripe_account' => [
                'account_id' => $user->stripe_account_id,
                'charges_enabled' => $user->stripe_charges_enabled,
                'payouts_enabled' => $user->stripe_payouts_enabled,
                'details_submitted' => $user->stripe_details_submitted,
                'has_account' => !is_null($user->stripe_account_id),
            ],
            'kyc_status' => [
                'status' => $user->kyc_status,
                'can_sell' => $user->canSell(),
                'needs_kyc' => $user->needsKyc(),
                'stripe_identity_verified' => $user->stripe_identity_verified,
            ],
            'stats' => [
                'total_listings' => $user->cardListings()->count(),
                'active_listings' => $user->cardListings()->where('status', 'active')->count(),
                'total_orders' => $user->sellerOrders()->count(),
                'pending_orders' => $user->sellerOrders()->where('status', 'pending')->count(),
            ]
        ];

        return response()->json($dashboard);
    }

    /**
     * Aggiorna informazioni account Connect
     */
    public function updateAccount(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'message' => 'Account Stripe Connect non trovato'
            ], 404);
        }

        $request->validate([
            'business_type' => 'sometimes|in:individual,company',
            'business_profile' => 'sometimes|array',
            'business_profile.name' => 'sometimes|string|max:255',
            'business_profile.url' => 'sometimes|url',
            'business_profile.mcc' => 'sometimes|string',
        ]);

        $result = $this->stripeService->updateConnectAccount(
            $user->stripe_account_id, 
            $request->only(['business_type', 'business_profile'])
        );

        if (!$result['success']) {
            return response()->json([
                'message' => 'Errore nell\'aggiornamento dell\'account',
                'error' => $result['error']
            ], 500);
        }

        return response()->json([
            'message' => 'Account aggiornato con successo',
            'account' => $result['account']
        ]);
    }

    /**
     * Elimina account Connect
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'message' => 'Account Stripe Connect non trovato'
            ], 404);
        }

        $result = $this->stripeService->deleteConnectAccount($user->stripe_account_id);

        if (!$result['success']) {
            return response()->json([
                'message' => 'Errore nell\'eliminazione dell\'account',
                'error' => $result['error']
            ], 500);
        }

        // Rimuovi riferimenti dall'utente
        $user->update([
            'stripe_account_id' => null,
            'stripe_charges_enabled' => false,
            'stripe_payouts_enabled' => false,
            'stripe_details_submitted' => false,
        ]);

        return response()->json([
            'message' => 'Account Stripe Connect eliminato con successo'
        ]);
    }
}