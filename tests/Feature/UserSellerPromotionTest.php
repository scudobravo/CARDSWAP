<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSellerPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_kyc_alone_does_not_promote_buyer_to_seller(): void
    {
        $user = User::factory()->create([
            'role' => 'buyer',
            'kyc_status' => 'not_submitted',
        ]);

        $user->updateKycStatus('approved');
        $user->refresh();

        $this->assertSame('buyer', $user->role);
        $this->assertTrue($user->canUseBuyerFeatures());
        $this->assertFalse($user->canSellWithStripe());
    }

    public function test_kyc_and_stripe_connect_promote_buyer_to_seller(): void
    {
        $user = User::factory()->create([
            'role' => 'buyer',
            'kyc_status' => 'approved',
            'stripe_account_id' => 'acct_test_123',
            'stripe_charges_enabled' => false,
            'stripe_payouts_enabled' => false,
        ]);

        $user->updateStripeAccountStatus([
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
        ]);
        $user->refresh();

        $this->assertSame('seller', $user->role);
        $this->assertTrue($user->canSellWithStripe());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'role_update',
        ]);
    }

    public function test_stripe_identity_verification_promotes_when_connect_is_ready(): void
    {
        $user = User::factory()->create([
            'role' => 'buyer',
            'kyc_status' => 'pending',
            'stripe_account_id' => 'acct_test_456',
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => true,
            'stripe_identity_verified' => false,
        ]);

        $user->markStripeIdentityVerified();
        $user->refresh();

        $this->assertSame('seller', $user->role);
        $this->assertTrue($user->canSellWithStripe());
    }

    public function test_admin_role_is_not_changed_by_promotion(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'kyc_status' => 'approved',
            'stripe_account_id' => 'acct_test_admin',
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => true,
        ]);

        $this->assertFalse($user->promoteToSellerIfEligible());
        $this->assertSame('admin', $user->fresh()->role);
    }
}
