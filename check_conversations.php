<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$conversations = App\Models\OrderConversation::with('buyer', 'seller', 'listing')->get();

echo "Total conversations: " . $conversations->count() . "\n\n";

foreach ($conversations as $c) {
    echo "=== Conversation ID: " . $c->id . " ===\n";
    echo "Buyer: " . ($c->buyer->name ?? 'N/A') . " (ID: " . $c->buyer_id . ")\n";
    echo "Seller: " . ($c->seller->name ?? 'N/A') . " (ID: " . $c->seller_id . ")\n";
    echo "Listing ID: " . ($c->listing_id ?? 'N/A') . "\n";
    echo "Order ID: " . ($c->order_id ?? 'N/A') . "\n";
    echo "Status: " . $c->status . "\n";
    echo "Messages count: " . $c->messages()->count() . "\n";
    echo "\n";
}

// Verifica anche per un utente specifico (es. ID 1)
echo "\n=== Conversations for User ID 1 ===\n";
$userConversations = App\Models\OrderConversation::where('buyer_id', 1)
    ->orWhere('seller_id', 1)
    ->with('buyer', 'seller', 'listing')
    ->get();

echo "Total: " . $userConversations->count() . "\n";
foreach ($userConversations as $c) {
    echo "ID: " . $c->id . " | Buyer: " . $c->buyer_id . " | Seller: " . $c->seller_id . "\n";
}

