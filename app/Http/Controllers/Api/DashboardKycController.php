<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardKycController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Mostra lo stato KYC dell'utente
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'kyc_status' => $user->kyc_status,
                'stripe_identity_verified' => $user->stripe_identity_verified,
                'stripe_identity_verified_at' => $user->stripe_identity_verified_at,
                'account_type' => $user->account_type,
                'business_name' => $user->business_name,
                'vat_number' => $user->vat_number,
                'is_kyc_complete' => $user->kyc_status === 'approved' && $user->stripe_identity_verified,
                'requires_kyc' => $user->kyc_status !== 'approved' || !$user->stripe_identity_verified
            ]
        ]);
    }

    /**
     * Avvia il processo KYC con Stripe Identity
     */
    public function startKyc(Request $request): JsonResponse
    {
        $user = $request->user();

        // Verifica se l'utente ha già una sessione di verifica attiva
        if ($user->stripe_verification_session_id) {
            $sessionStatus = $this->stripeService->getVerificationSessionStatus($user->stripe_verification_session_id);
            
            if ($sessionStatus['success'] && $sessionStatus['status'] === 'requires_input') {
                return response()->json([
                    'success' => true,
                    'message' => 'Sessione di verifica già attiva',
                    'data' => [
                        'verification_url' => $sessionStatus['url'],
                        'session_id' => $user->stripe_verification_session_id
                    ]
                ]);
            }
        }

        // Crea nuova sessione di verifica
        $result = $this->stripeService->createIdentityVerificationSession($user);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione della sessione di verifica',
                'error' => $result['error']
            ], 500);
        }

        // Salva l'ID della sessione
        $user->update([
            'stripe_verification_session_id' => $result['session_id'],
            'kyc_status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sessione di verifica creata con successo',
            'data' => [
                'verification_url' => $result['url'],
                'session_id' => $result['session_id']
            ]
        ]);
    }

    /**
     * Verifica lo stato della sessione KYC
     */
    public function checkKycStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->stripe_verification_session_id) {
            return response()->json([
                'success' => false,
                'message' => 'Nessuna sessione di verifica attiva'
            ], 400);
        }

        $result = $this->stripeService->getVerificationSessionStatus($user->stripe_verification_session_id);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dello stato della verifica',
                'error' => $result['error']
            ], 500);
        }

        // Aggiorna lo stato dell'utente se necessario
        if ($result['status'] === 'verified') {
            $user->update([
                'kyc_status' => 'approved',
                'stripe_identity_verified' => true,
                'stripe_identity_verified_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $result['status'],
                'kyc_status' => $user->kyc_status,
                'stripe_identity_verified' => $user->stripe_identity_verified,
                'is_complete' => $result['status'] === 'verified'
            ]
        ]);
    }

    /**
     * Completa il profilo utente (dati aggiuntivi per KYC)
     */
    public function completeProfile(Request $request): JsonResponse
    {
        $request->validate([
            'fiscal_code' => 'required|string|max:20',
            'birth_date' => 'required|date|before:today',
            'birth_place' => 'required|string|max:255',
            'nationality' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        
        $user->update([
            'fiscal_code' => $request->fiscal_code,
            'birth_date' => $request->birth_date,
            'birth_place' => $request->birth_place,
            'nationality' => $request->nationality,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profilo completato con successo',
            'data' => [
                'user' => $user->fresh()
            ]
        ]);
    }
}