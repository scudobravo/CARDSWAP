<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 DEBUG MENU E DASHBOARD\n";
echo "========================\n\n";

try {
    // 1. Verifica connessione database
    echo "1. 📊 VERIFICA DATABASE:\n";
    $result = DB::select('SELECT 1 as test');
    echo "   ✅ Connessione database OK\n\n";
    
    // 2. Verifica utenti nel database
    echo "2. 👥 UTENTI NEL DATABASE:\n";
    $users = DB::table('users')->select('id', 'name', 'email', 'kyc_status', 'created_at')->get();
    echo "   Trovati " . count($users) . " utenti:\n";
    foreach ($users as $user) {
        echo "   - ID: {$user->id}, Nome: {$user->name}, Email: {$user->email}, KYC: {$user->kyc_status}\n";
    }
    echo "\n";
    
    // 3. Verifica inserzioni nel database
    echo "3. 🃏 INSERZIONI NEL DATABASE:\n";
    $listings = DB::table('card_listings')->select('id', 'seller_id', 'status', 'created_at')->get();
    echo "   Trovate " . count($listings) . " inserzioni:\n";
    foreach ($listings as $listing) {
        echo "   - ID: {$listing->id}, Seller: {$listing->seller_id}, Status: {$listing->status}\n";
    }
    echo "\n";
    
    // 4. Verifica ordini nel database
    echo "4. 📦 ORDINI NEL DATABASE:\n";
    $orders = DB::table('orders')->select('id', 'buyer_id', 'seller_id', 'status', 'created_at')->get();
    echo "   Trovati " . count($orders) . " ordini:\n";
    foreach ($orders as $order) {
        echo "   - ID: {$order->id}, Buyer: {$order->buyer_id}, Seller: {$order->seller_id}, Status: {$order->status}\n";
    }
    echo "\n";
    
    // 5. Verifica wishlist nel database
    echo "5. ❤️ WISHLIST NEL DATABASE:\n";
    $wishlist = DB::table('wishlists')->select('id', 'user_id', 'card_model_id', 'created_at')->get();
    echo "   Trovati " . count($wishlist) . " elementi wishlist:\n";
    foreach ($wishlist as $item) {
        echo "   - ID: {$item->id}, User: {$item->user_id}, Card Model: {$item->card_model_id}\n";
    }
    echo "\n";
    
    // 6. Verifica notifiche nel database
    echo "6. 🔔 NOTIFICHE NEL DATABASE:\n";
    $notifications = DB::table('user_notifications')->select('id', 'user_id', 'read_at', 'created_at')->get();
    echo "   Trovate " . count($notifications) . " notifiche:\n";
    foreach ($notifications as $notification) {
        echo "   - ID: {$notification->id}, User: {$notification->user_id}, Read: " . ($notification->read_at ? 'Sì' : 'No') . "\n";
    }
    echo "\n";
    
    // 7. Test API dashboard
    echo "7. 🌐 TEST API DASHBOARD:\n";
    echo "   URL: http://localhost:8000/api/dashboard\n";
    echo "   (Esegui: curl -H 'Authorization: Bearer YOUR_TOKEN' http://localhost:8000/api/dashboard)\n\n";
    
    // 8. Verifica token nel localStorage
    echo "8. 🔑 VERIFICA TOKEN:\n";
    echo "   Controlla nel browser:\n";
    echo "   - Apri DevTools (F12)\n";
    echo "   - Vai su Application > Local Storage\n";
    echo "   - Cerca 'token' e verifica se esiste\n";
    echo "   - Se esiste, copia il valore e testa l'API\n\n";
    
    echo "✅ DEBUG COMPLETATO\n";
    
} catch (Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
