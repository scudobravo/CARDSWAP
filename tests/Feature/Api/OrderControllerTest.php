<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CardListing;
use App\Models\CardModel;
use App\Models\Category;
use App\Models\CardSet;
use App\Models\Player;
use App\Models\Team;
use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $seller;
    protected $cardModel;
    protected $listing;
    protected $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'buyer',
            'email_verified_at' => now()
        ]);

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'kyc_status' => 'approved'
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

        $this->listing = CardListing::factory()->create([
            'card_model_id' => $this->cardModel->id,
            'seller_id' => $this->seller->id,
            'price' => 10.00,
            'quantity' => 5,
            'status' => 'active'
        ]);

        $this->order = Order::factory()->create([
            'buyer_id' => $this->user->id,
            'seller_id' => $this->seller->id,
            'status' => 'paid',
            'subtotal' => 10.00,
            'shipping_cost' => 5.00,
            'tax_amount' => 2.20,
            'total_amount' => 17.20
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'card_listing_id' => $this->listing->id,
            'quantity' => 1,
            'price' => 10.00,
            'condition' => 'Mint'
        ]);
    }

    /** @test */
    public function user_can_view_their_orders()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Ordini recuperati con successo'
                ])
                ->assertJsonCount(1, 'data');

        $orderData = $response->json('data')[0];
        $this->assertEquals($this->order->id, $orderData['id']);
        $this->assertEquals($this->user->id, $orderData['buyer_id']);
        $this->assertEquals($this->seller->id, $orderData['seller_id']);
    }

    /** @test */
    public function user_can_view_specific_order_details()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/orders/{$this->order->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Ordine recuperato con successo'
                ]);

        $orderData = $response->json('order');
        $this->assertEquals($this->order->id, $orderData['id']);
        $this->assertEquals($this->user->id, $orderData['buyer_id']);
        $this->assertEquals($this->seller->id, $orderData['seller_id']);

        // Verifica che gli articoli dell'ordine siano inclusi
        $this->assertArrayHasKey('order_items', $response->json());
        $orderItems = $response->json('order_items');
        $this->assertCount(1, $orderItems);
        $this->assertEquals($this->listing->id, $orderItems[0]['id']);
    }

    /** @test */
    public function seller_can_view_their_orders()
    {
        Sanctum::actingAs($this->seller);

        $response = $this->getJson('/api/orders/seller');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Ordini venditore recuperati con successo'
                ])
                ->assertJsonCount(1, 'data');

        $orderData = $response->json('data')[0];
        $this->assertEquals($this->order->id, $orderData['id']);
        $this->assertEquals($this->seller->id, $orderData['seller_id']);
    }

    /** @test */
    public function seller_can_update_order_status()
    {
        Sanctum::actingAs($this->seller);

        $response = $this->putJson("/api/orders/{$this->order->id}/status", [
            'status' => 'shipped',
            'tracking_number' => 'TRK123456789',
            'notes' => 'Spedito con corriere espresso'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Stato ordine aggiornato con successo'
                ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'shipped',
            'tracking_number' => 'TRK123456789',
            'notes' => 'Spedito con corriere espresso'
        ]);

        // Verifica che la data di spedizione sia stata impostata
        $order = Order::find($this->order->id);
        $this->assertNotNull($order->shipped_at);
    }

    /** @test */
    public function seller_can_mark_order_as_delivered()
    {
        // Prima imposta come spedito
        $this->order->update([
            'status' => 'shipped',
            'shipped_at' => now()
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->putJson("/api/orders/{$this->order->id}/status", [
            'status' => 'delivered'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'delivered'
        ]);

        // Verifica che la data di consegna sia stata impostata
        $order = Order::find($this->order->id);
        $this->assertNotNull($order->delivered_at);
    }

    /** @test */
    public function seller_can_cancel_order()
    {
        Sanctum::actingAs($this->seller);

        $response = $this->putJson("/api/orders/{$this->order->id}/status", [
            'status' => 'cancelled',
            'notes' => 'Ordine annullato per richiesta cliente'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'cancelled',
            'notes' => 'Ordine annullato per richiesta cliente'
        ]);
    }

    /** @test */
    public function user_cannot_view_other_users_orders()
    {
        $otherUser = User::factory()->create();
        $otherOrder = Order::factory()->create([
            'buyer_id' => $otherUser->id,
            'seller_id' => $this->seller->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/orders/{$otherOrder->id}");

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Ordine non trovato'
                ]);
    }

    /** @test */
    public function seller_cannot_update_other_sellers_orders()
    {
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $otherOrder = Order::factory()->create([
            'buyer_id' => $this->user->id,
            'seller_id' => $otherSeller->id
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->putJson("/api/orders/{$otherOrder->id}/status", [
            'status' => 'shipped'
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Ordine non trovato'
                ]);
    }

    /** @test */
    public function order_status_update_validates_status()
    {
        Sanctum::actingAs($this->seller);

        $response = $this->putJson("/api/orders/{$this->order->id}/status", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Dati di validazione non validi'
                ])
                ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function order_status_update_requires_authentication()
    {
        $this->withoutMiddleware();

        $response = $this->putJson("/api/orders/{$this->order->id}/status", [
            'status' => 'shipped'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function order_view_requires_authentication()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/orders');
        $response->assertStatus(401);

        $response = $this->getJson("/api/orders/{$this->order->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_access_nonexistent_order()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/orders/99999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Ordine non trovato'
                ]);
    }

    /** @test */
    public function seller_cannot_update_nonexistent_order()
    {
        Sanctum::actingAs($this->seller);

        $response = $this->putJson('/api/orders/99999/status', [
            'status' => 'shipped'
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Ordine non trovato'
                ]);
    }

    /** @test */
    public function order_details_include_seller_and_buyer_info()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/orders/{$this->order->id}");

        $response->assertStatus(200);

        $orderData = $response->json('order');
        $this->assertArrayHasKey('seller', $orderData);
        $this->assertArrayHasKey('buyer', $orderData);
        $this->assertEquals($this->seller->id, $orderData['seller']['id']);
        $this->assertEquals($this->user->id, $orderData['buyer']['id']);
    }

    /** @test */
    public function order_items_include_card_model_info()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/orders/{$this->order->id}");

        $response->assertStatus(200);

        $orderItems = $response->json('order_items');
        $this->assertCount(1, $orderItems);
        
        $item = $orderItems[0];
        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('condition', $item);
        $this->assertArrayHasKey('price', $item);
        $this->assertArrayHasKey('quantity', $item);
        $this->assertArrayHasKey('seller_name', $item);
    }

    /** @test */
    public function orders_are_ordered_by_creation_date_desc()
    {
        // Crea un altro ordine più recente
        $newerOrder = Order::factory()->create([
            'buyer_id' => $this->user->id,
            'seller_id' => $this->seller->id,
            'created_at' => now()->addHour()
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertCount(2, $orders);
        
        // Verifica che l'ordine più recente sia primo
        $this->assertEquals($newerOrder->id, $orders[0]['id']);
        $this->assertEquals($this->order->id, $orders[1]['id']);
    }
}
