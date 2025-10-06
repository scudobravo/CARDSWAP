<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CardListing;
use App\Models\Order;
use App\Notifications\ListingPublished;
use App\Notifications\ListingSold;
use App\Notifications\WishlistItemAvailable;

class TestEmailNotifications extends Command
{
    protected $signature = 'test:email-notifications {email}';
    protected $description = 'Test email notifications by sending them to a specific email address';

    public function handle()
    {
        $email = $this->argument('email');
        
        // Crea un utente di test
        $user = User::factory()->create([
            'email' => $email,
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'seller',
            'kyc_status' => 'approved',
            'stripe_identity_verified' => true
        ]);

        // Crea una carta di test
        $cardModel = \App\Models\CardModel::factory()->create();
        $listing = CardListing::factory()->create([
            'seller_id' => $user->id,
            'card_model_id' => $cardModel->id,
            'status' => 'active',
            'published_at' => now()
        ]);

        $this->info("Testing email notifications for: {$email}");

        // Test 1: Listing Published
        $this->info("1. Testing ListingPublished notification...");
        $user->notify(new ListingPublished($listing));
        $this->info("   ✓ ListingPublished sent");

        // Test 2: Wishlist Item Available
        $this->info("2. Testing WishlistItemAvailable notification...");
        $user->notify(new WishlistItemAvailable($listing));
        $this->info("   ✓ WishlistItemAvailable sent");

        // Test 3: Listing Sold
        $this->info("3. Testing ListingSold notification...");
        $order = Order::factory()->create([
            'buyer_id' => $user->id,
            'order_number' => 'TEST-' . time(),
            'total_amount' => $listing->price,
            'status' => 'confirmed'
        ]);
        
        $order->orderItems()->create([
            'card_listing_id' => $listing->id,
            'quantity' => 1,
            'price' => $listing->price
        ]);

        $user->notify(new ListingSold($listing, $order));
        $this->info("   ✓ ListingSold sent");

        $this->info("\n🎉 All email notifications sent successfully!");
        $this->info("Check your email inbox or Mailtrap dashboard to see the results.");
        
        // Cleanup
        $user->delete();
        $listing->delete();
        $order->delete();
        $cardModel->delete();
    }
}
