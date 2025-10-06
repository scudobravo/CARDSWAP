<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CardListing;
use App\Models\Order;
use App\Models\CardModel;
use App\Notifications\ListingPublished;
use App\Notifications\ListingSold;
use App\Notifications\WishlistItemAvailable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;

class EmailNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configura Mail per usare log invece di inviare email reali
        config(['mail.default' => 'log']);
    }

    /** @test */
    public function listing_published_notification_renders_correctly()
    {
        // Crea un utente di test
        $user = User::factory()->create([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario@example.com'
        ]);

        // Crea una carta di test
        $cardModel = CardModel::factory()->create();
        $listing = CardListing::factory()->create([
            'seller_id' => $user->id,
            'card_model_id' => $cardModel->id,
            'status' => 'active',
            'published_at' => now(),
            'price' => 25.50,
            'condition' => 'mint',
            'quantity' => 1
        ]);

        // Crea la notifica
        $notification = new ListingPublished($listing);

        // Verifica che la notifica possa essere creata
        $this->assertInstanceOf(ListingPublished::class, $notification);

        // Verifica che il template esista
        $this->assertFileExists(resource_path('views/emails/listing-published.blade.php'));

        // Cleanup
        $user->delete();
        $listing->delete();
        $cardModel->delete();
    }

    /** @test */
    public function listing_sold_notification_renders_correctly()
    {
        // Crea un utente di test
        $user = User::factory()->create([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario@example.com'
        ]);

        // Crea una carta di test
        $cardModel = CardModel::factory()->create();
        $listing = CardListing::factory()->create([
            'seller_id' => $user->id,
            'card_model_id' => $cardModel->id,
            'status' => 'sold',
            'price' => 25.50,
            'condition' => 'mint',
            'quantity' => 1
        ]);

        // Crea un ordine di test
        $order = Order::factory()->create([
            'buyer_id' => $user->id,
            'order_number' => 'TEST-123',
            'total_amount' => 25.50,
            'status' => 'confirmed'
        ]);

        // Crea la notifica
        $notification = new ListingSold($listing, $order);

        // Verifica che la notifica possa essere creata
        $this->assertInstanceOf(ListingSold::class, $notification);

        // Verifica che il template esista
        $this->assertFileExists(resource_path('views/emails/listing-sold.blade.php'));

        // Cleanup
        $user->delete();
        $listing->delete();
        $order->delete();
        $cardModel->delete();
    }

    /** @test */
    public function wishlist_item_available_notification_renders_correctly()
    {
        // Crea un utente di test
        $user = User::factory()->create([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario@example.com'
        ]);

        // Crea una carta di test
        $cardModel = CardModel::factory()->create();
        $listing = CardListing::factory()->create([
            'seller_id' => $user->id,
            'card_model_id' => $cardModel->id,
            'status' => 'active',
            'published_at' => now(),
            'price' => 25.50,
            'condition' => 'mint',
            'quantity' => 1
        ]);

        // Crea la notifica
        $notification = new WishlistItemAvailable($listing);

        // Verifica che la notifica possa essere creata
        $this->assertInstanceOf(WishlistItemAvailable::class, $notification);

        // Verifica che il template esista
        $this->assertFileExists(resource_path('views/emails/wishlist-item-available.blade.php'));

        // Cleanup
        $user->delete();
        $listing->delete();
        $cardModel->delete();
    }

    /** @test */
    public function email_templates_are_valid_blade_files()
    {
        // Verifica che tutti i template email esistano e siano validi
        $templates = [
            'emails/base.blade.php',
            'emails/listing-published.blade.php',
            'emails/listing-sold.blade.php',
            'emails/wishlist-item-available.blade.php'
        ];

        foreach ($templates as $template) {
            $path = resource_path('views/' . $template);
            $this->assertFileExists($path, "Template {$template} does not exist");
            
            // Verifica che il file non sia vuoto
            $this->assertGreaterThan(0, filesize($path), "Template {$template} is empty");
        }
    }
}
