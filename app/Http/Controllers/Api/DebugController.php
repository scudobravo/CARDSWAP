<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Route di debug (token, auth). Solo per sviluppo/test.
 * Usato per evitare Closure nelle route e permettere route:cache in produzione.
 */
class DebugController extends Controller
{
    public function debugTokens(): JsonResponse
    {
        $users = DB::table('users')
            ->select('id', 'name', 'email', 'remember_token', 'api_token', 'kyc_status')
            ->get();

        return response()->json([
            'users' => $users,
            'message' => 'Usa uno di questi token con /api/debug-auth?token=TOKEN'
        ]);
    }

    public function debugAuth(Request $request): JsonResponse
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'error' => 'Token mancante',
                'message' => 'Aggiungi ?token=YOUR_TOKEN alla URL'
            ], 400);
        }

        try {
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

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'kyc_status' => $user->kyc_status,
                'created_at' => $user->created_at
            ];

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
    }
}
