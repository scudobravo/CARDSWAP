<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\CardListing;
use App\Models\CardModel;
use App\Models\Category;
use App\Models\CardSet;
use App\Models\Player;
use App\Models\Team;
use App\Models\League;
use App\Models\ShippingZone;
use App\Models\Wishlist;
use App\Models\Order;
use App\Models\OrderItem;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;

class WorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $seller;
    protected $buyer;
    protected $cardModel;
    protected $shippingZone;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crea un venditore verificato
        $this->seller = User::factory()->create([
            'role' => 'seller',
            'kyc_status' => 'approved',
            'stripe_identity_verified' => true,
            'email_verified_at' => now()
        ]);

        // Crea un acquirente
        $this->buyer = User::factory()->create([
            'role' => 'buyer',
            'email_verified_at' => now()
        ]);

        // Crea dati di test
        $category = Category::factory()->create(['name' => 'Calcio']);
        $cardSet = CardSet::factory()->create(['name' => 'Panini 2024']);
        $player = Player::factory()->create(['name' => 'Lionel Messi']);
        $team = Team::factory()->create(['name' => 'Inter Miami']);
        $league = League::factory()->create(['name' => 'MLS']);

        $this->cardModel = CardModel::factory()->create([
            'category_id' => $category->id,
            'card_set_id' => $cardSet->id,
            'player_id' => $player->id,
            'team_id' => $team->id,
            'league_id' => $league->id,
        ]);

        $this->shippingZone = ShippingZone::factory()->create([
            'name' => 'Italia',
            'shipping_cost' => 5.00
        ]);

        Sanctum::actingAs($this->seller);
    }

    /** @test */
    public function verified_seller_can_publish_listing_automatically()
    {
        Notification::fake();
        Event::fake();

        $listingData = [
            'card_model_id' => $this->cardModel->id,
            'price' => 25.50,
            'condition' => 'mint',
            'quantity' => 1,
            'language' => 'italian',
            'is_foil' => false,
            'is_signed' => false,
            'is_altered' => false,
            'is_first_edition' => false,
            'is_negotiable' => false,
            'description' => 'Carta in perfette condizioni',
            'shipping_zones' => [$this->shippingZone->id]
        ];

        $response = $this->postJson('/api/listings', $listingData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Inserzione pubblicata con successo'
                ]);

        // Verifica che l'inserzione sia stata pubblicata automaticamente
        $listing = CardListing::where('card_model_id', $this->cardModel->id)->first();
        $this->assertNotNull($listing, 'Inserzione non trovata');
        $this->assertEquals('active', $listing->status);
        $this->assertNotNull($listing->published_at, 'published_at non impostato');

        // Verifica che l'evento sia stato triggerato
        Event::assertDispatched(\App\Events\ListingStatusChanged::class);
    }

    /** @test */
    public function unverified_seller_requires_manual_approval()
    {
        // Crea un venditore non verificato (ma con KYC approvato per superare il middleware)
        $unverifiedSeller = User::factory()->create([
            'role' => 'seller',
            'kyc_status' => 'approved',
            'stripe_identity_verified' => false, // Non verificato da Stripe
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($unverifiedSeller);

        $listingData = [
            'card_model_id' => $this->cardModel->id,
            'price' => 25.50,
            'condition' => 'mint',
            'quantity' => 1,
            'language' => 'italian',
            'shipping_zones' => [$this->shippingZone->id]
        ];

        $response = $this->postJson('/api/listings', $listingData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Inserzione inviata per revisione'
                ]);

        // Verifica che l'inserzione sia in attesa di revisione
        $listing = CardListing::where('card_model_id', $this->cardModel->id)->first();
        $this->assertEquals('pending_review', $listing->status);
    }

    /** @test */
    public function listing_status_transitions_work_correctly()
    {
        $listing = CardListing::factory()->create([
            'seller_id' => $this->seller->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'draft'
        ]);

        // Test transizioni di stato
        $this->assertTrue($listing->isDraft());
        
        $listing->submitForReview();
        $this->assertTrue($listing->isPendingReview());
        
        $listing->approve();
        $this->assertTrue($listing->isApproved());
        
        $listing->publish();
        $this->assertTrue($listing->status === 'active');
        
        $listing->pause();
        $this->assertTrue($listing->isPaused());
        
        $listing->activate();
        $this->assertTrue($listing->status === 'active');
        
        $listing->markAsSold();
        $this->assertTrue($listing->status === 'sold');
    }

    /** @test */
    public function wishlist_notifications_are_sent_when_listing_is_published()
    {
        Notification::fake();
        Event::fake();

        // Crea un utente con la carta nella wishlist
        $wishlistUser = User::factory()->create([
            'role' => 'buyer',
            'email_verified_at' => now()
        ]);

        Wishlist::create([
            'user_id' => $wishlistUser->id,
            'card_model_id' => $this->cardModel->id
        ]);

        // Crea un'inserzione
        $listing = CardListing::factory()->create([
            'seller_id' => $this->seller->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'draft'
        ]);

        // Pubblica l'inserzione
        $listing->publish();
        event(new \App\Events\ListingStatusChanged($listing, 'draft', 'active'));

        // Verifica che la notifica sia stata inviata
        Notification::assertSentTo(
            $wishlistUser,
            \App\Notifications\WishlistItemAvailable::class
        );
    }

    /** @test */
    public function seller_receives_notification_when_listing_is_published()
    {
        Notification::fake();
        Event::fake();

        $listing = CardListing::factory()->create([
            'seller_id' => $this->seller->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'draft'
        ]);

        // Pubblica l'inserzione
        $listing->publish();
        event(new \App\Events\ListingStatusChanged($listing, 'draft', 'active'));

        // Verifica che la notifica sia stata inviata al venditore
        Notification::assertSentTo(
            $this->seller,
            \App\Notifications\ListingPublished::class
        );
    }

    /** @test */
    public function notifications_are_sent_when_listing_is_sold()
    {
        Notification::fake();
        Event::fake();

        // Crea un'inserzione
        $listing = CardListing::factory()->create([
            'seller_id' => $this->seller->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'active',
            'quantity' => 1
        ]);

        // Crea un ordine
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'status' => 'confirmed'
        ]);

        // Simula la vendita
        $listing->markAsSold();
        event(new \App\Events\ListingSold($listing, $order));

        // Verifica che le notifiche siano state inviate
        Notification::assertSentTo(
            $this->seller,
            \App\Notifications\ListingSold::class
        );

        Notification::assertSentTo(
            $this->buyer,
            \App\Notifications\ListingSold::class
        );
    }
}