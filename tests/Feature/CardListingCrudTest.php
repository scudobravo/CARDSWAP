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
use Laravel\Sanctum\Sanctum;

class CardListingCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $cardModel;
    protected $shippingZone;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crea un utente venditore
        $this->user = User::factory()->create([
            'role' => 'seller',
            'kyc_status' => 'approved',
            'stripe_identity_verified' => true,
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

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function seller_can_create_card_listing()
    {
        $listingData = [
            'card_model_id' => $this->cardModel->id,
            'price' => 25.50,
            'condition' => 'mint',
            'quantity' => 1,
            'language' => 'italian',
            'is_foil' => true,
            'is_signed' => false,
            'is_altered' => false,
            'is_first_edition' => true,
            'is_negotiable' => true,
            'description' => 'Carta in perfette condizioni',
            'images' => ['image1.jpg', 'image2.jpg'],
            'shipping_zones' => [$this->shippingZone->id],
            'status' => 'draft'
        ];

        $response = $this->postJson('/api/listings', $listingData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Inserzione creata con successo'
                ]);

        $this->assertDatabaseHas('card_listings', [
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'price' => 25.50,
            'condition' => 'mint',
            'quantity' => 1,
            'language' => 'italian',
            'is_foil' => true,
            'is_signed' => false,
            'is_altered' => false,
            'is_first_edition' => true,
            'is_negotiable' => true,
            'status' => 'draft'
        ]);
    }

    /** @test */
    public function seller_can_view_their_listings()
    {
        CardListing::factory()->create([
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'active'
        ]);

        $response = $this->getJson('/api/listings/my/listings');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function seller_can_update_their_listing()
    {
        $listing = CardListing::factory()->create([
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'price' => 20.00
        ]);

        $updateData = [
            'price' => 30.00,
            'quantity' => 2,
            'description' => 'Prezzo aggiornato',
            'status' => 'active'
        ];

        $response = $this->putJson("/api/listings/{$listing->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Inserzione aggiornata con successo'
                ]);

        $this->assertDatabaseHas('card_listings', [
            'id' => $listing->id,
            'price' => 30.00,
            'quantity' => 2,
            'description' => 'Prezzo aggiornato'
        ]);
    }

    /** @test */
    public function seller_can_change_listing_status()
    {
        $listing = CardListing::factory()->create([
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'draft'
        ]);

        $response = $this->patchJson("/api/listings/{$listing->id}/status", [
            'status' => 'active'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Status inserzione aggiornato con successo'
                ]);

        $this->assertDatabaseHas('card_listings', [
            'id' => $listing->id,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function seller_can_duplicate_listing()
    {
        $listing = CardListing::factory()->create([
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'active'
        ]);

        $response = $this->postJson("/api/listings/{$listing->id}/duplicate");

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Inserzione duplicata con successo'
                ]);

        $this->assertDatabaseHas('card_listings', [
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'draft'
        ]);
    }

    /** @test */
    public function seller_can_get_listing_stats()
    {
        CardListing::factory()->create([
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'active',
            'price' => 25.00,
            'quantity' => 2
        ]);

        CardListing::factory()->create([
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'draft'
        ]);

        $response = $this->getJson('/api/listings/my/stats');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'total_listings' => 2,
                        'active_listings' => 1,
                        'draft_listings' => 1,
                        'total_value' => 50.00
                    ]
                ]);
    }

    /** @test */
    public function seller_can_bulk_update_status()
    {
        $listing1 = CardListing::factory()->create([
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'draft'
        ]);

        $listing2 = CardListing::factory()->create([
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'draft'
        ]);

        $response = $this->patchJson('/api/listings/bulk/status', [
            'listing_ids' => [$listing1->id, $listing2->id],
            'status' => 'active'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Status aggiornato per 2 inserzioni',
                    'updated_count' => 2
                ]);

        $this->assertDatabaseHas('card_listings', [
            'id' => $listing1->id,
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('card_listings', [
            'id' => $listing2->id,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function seller_can_export_listings()
    {
        CardListing::factory()->create([
            'seller_id' => $this->user->id,
            'card_model_id' => $this->cardModel->id,
            'status' => 'active'
        ]);

        $response = $this->getJson('/api/listings/my/export');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function seller_cannot_update_other_sellers_listing()
    {
        $otherUser = User::factory()->create(['role' => 'seller']);
        $listing = CardListing::factory()->create([
            'seller_id' => $otherUser->id,
            'card_model_id' => $this->cardModel->id
        ]);

        $response = $this->putJson("/api/listings/{$listing->id}", [
            'price' => 30.00
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Non autorizzato a modificare questa inserzione'
                ]);
    }

    /** @test */
    public function validation_works_for_required_fields()
    {
        $response = $this->postJson('/api/listings', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'card_model_id',
                    'price',
                    'condition',
                    'quantity',
                    'language',
                    'shipping_zones'
                ]);
    }

    /** @test */
    public function validation_works_for_price_range()
    {
        $response = $this->postJson('/api/listings', [
            'card_model_id' => $this->cardModel->id,
            'price' => -10.00, // Prezzo negativo
            'condition' => 'mint',
            'quantity' => 1,
            'language' => 'italian',
            'shipping_zones' => [$this->shippingZone->id]
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['price']);
    }

    /** @test */
    public function validation_works_for_condition_values()
    {
        $response = $this->postJson('/api/listings', [
            'card_model_id' => $this->cardModel->id,
            'price' => 25.00,
            'condition' => 'invalid_condition',
            'quantity' => 1,
            'language' => 'italian',
            'shipping_zones' => [$this->shippingZone->id]
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['condition']);
    }
}
