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

        // Verifica che l'utente sia un venditore
        if (!$user->isSeller()) {
            return response()->json([
                'message' => 'Solo i venditori possono creare account Stripe Connect'
            ], 403);
        }

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

        $returnUrl = config('app.url') . '/dashboard/stripe/return';
        $refreshUrl = config('app.url') . '/dashboard/stripe/refresh';

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
        $user->update([
            'stripe_charges_enabled' => $result['charges_enabled'],
            'stripe_payouts_enabled' => $result['payouts_enabled'],
            'stripe_details_submitted' => $result['details_submitted'],
        ]);

        return response()->json([
            'account_id' => $user->stripe_account_id,
            'charges_enabled' => $result['charges_enabled'],
            'payouts_enabled' => $result['payouts_enabled'],
            'details_submitted' => $result['details_submitted'],
            'account' => $result['account']
        ]);
    }

    /**
     * Verifica se l'utente può ricevere pagamenti
     */
    public function canReceivePayments(Request $request)
    {
        $user = $request->user();

        if (!$user->isSeller()) {
            return response()->json([
                'can_receive_payments' => false,
                'reason' => 'Utente non è un venditore'
            ]);
        }

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

        if (!$user->isSeller()) {
            return response()->json([
                'message' => 'Solo i venditori possono accedere alla dashboard'
            ], 403);
        }

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