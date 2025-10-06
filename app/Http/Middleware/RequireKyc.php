<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireKyc
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utente non autenticato',
                'requires_auth' => true
            ], 401);
        }

        // Verifica se l'utente ha completato il KYC
        // Per la creazione di inserzioni, basta avere KYC approvato
        if ($user->kyc_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Verifica identità richiesta',
                'requires_kyc' => true,
                'kyc_status' => $user->kyc_status,
                'stripe_identity_verified' => $user->stripe_identity_verified,
                'redirect_url' => '/dashboard/kyc'
            ], 403);
        }

        return $next($request);
    }
}