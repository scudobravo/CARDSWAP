<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Debug route per verificare lo stato dell'autenticazione
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

// Route per ottenere tutti i token disponibili (solo per debug)
Route::get('/debug-tokens', function () {
    $users = DB::table('users')
        ->select('id', 'name', 'email', 'remember_token', 'api_token', 'kyc_status')
        ->get();
        
    return response()->json([
        'users' => $users,
        'message' => 'Usa uno di questi token con /debug-auth?token=TOKEN'
    ]);
});