<?php

namespace Tests\Feature\Api;

use App\Models\CardListing;
use App\Models\CardModel;
use App\Models\Category;
use App\Models\CardSet;
use App\Models\League;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminListingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedListing(): CardListing
    {
        $seller = User::factory()->create(['role' => 'seller', 'kyc_status' => 'approved']);
        $category = Category::factory()->create(['slug' => 'calcio']);
        $cardSet = CardSet::factory()->create();
        $player = Player::factory()->create();
        $team = Team::factory()->create();
        $league = League::factory()->create();
        $cardModel = CardModel::factory()->create([
            'category_id' => $category->id,
            'card_set_id' => $cardSet->id,
            'player_id' => $player->id,
            'team_id' => $team->id,
            'league_id' => $league->id,
        ]);

        return CardListing::factory()->create([
            'seller_id' => $seller->id,
            'card_model_id' => $cardModel->id,
            'title' => 'Test listing admin',
            'status' => 'pending_review',
            'price' => 10.5,
            'quantity' => 1,
        ]);
    }

    public function test_non_admin_cannot_access_admin_listings(): void
    {
        $user = User::factory()->create(['role' => 'buyer']);
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/listings')->assertForbidden();
    }

    public function test_admin_can_list_and_update_listing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $listing = $this->seedListing();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/listings')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/admin/listings/' . $listing->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $listing->id);

        $this->patchJson('/api/admin/listings/' . $listing->id, [
            'status' => 'active',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $listing->refresh();
        $this->assertSame('active', $listing->status);
        $this->assertNotNull($listing->published_at);
    }

    public function test_cannot_change_status_from_sold(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $listing = $this->seedListing();
        $listing->update(['status' => 'sold']);

        Sanctum::actingAs($admin);

        $this->patchJson('/api/admin/listings/' . $listing->id, [
            'status' => 'active',
        ])->assertStatus(422);
    }
}
