<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StripeService;
use App\Models\User;

class TestStripeConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:test-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Stripe configuration and Identity setup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Stripe Configuration...');
        
        // Test 1: Check environment variables
        $this->info("\n1. Checking environment variables:");
        $stripeKey = config('services.stripe.key');
        $stripeSecret = config('services.stripe.secret');
        $stripeWebhookSecret = config('services.stripe.webhook_secret');
        $stripeConnectClientId = config('services.stripe.connect_client_id');
        $stripeIdentityEnabled = config('services.stripe.identity_enabled');
        
        $this->line("   STRIPE_KEY: " . ($stripeKey ? substr($stripeKey, 0, 10) . '...' : 'NOT SET'));
        $this->line("   STRIPE_SECRET: " . ($stripeSecret ? substr($stripeSecret, 0, 10) . '...' : 'NOT SET'));
        $this->line("   STRIPE_WEBHOOK_SECRET: " . ($stripeWebhookSecret ? 'SET' : 'NOT SET'));
        $this->line("   STRIPE_CONNECT_CLIENT_ID: " . ($stripeConnectClientId ? 'SET' : 'NOT SET'));
        $this->line("   STRIPE_IDENTITY_ENABLED: " . ($stripeIdentityEnabled ? 'true' : 'false'));
        
        // Test 2: Validate Stripe key format
        $this->info("\n2. Validating Stripe key format:");
        if ($stripeKey && str_starts_with($stripeKey, 'pk_')) {
            $this->info("   ✅ Stripe Key format is correct");
        } else {
            $this->error("   ❌ Stripe Key format is incorrect (should start with 'pk_')");
        }
        
        if ($stripeSecret && str_starts_with($stripeSecret, 'sk_')) {
            $this->info("   ✅ Stripe Secret format is correct");
        } else {
            $this->error("   ❌ Stripe Secret format is incorrect (should start with 'sk_')");
        }
        
        // Test 3: Test StripeService initialization
        $this->info("\n3. Testing StripeService initialization:");
        try {
            $stripeService = new StripeService();
            $this->info("   ✅ StripeService initialized successfully");
        } catch (\Exception $e) {
            $this->error("   ❌ StripeService initialization failed: " . $e->getMessage());
            return 1;
        }
        
        // Test 4: Test Stripe API connection
        $this->info("\n4. Testing Stripe API connection:");
        try {
            $stripe = new \Stripe\StripeClient($stripeSecret);
            $account = $stripe->accounts->retrieve();
            $this->info("   ✅ Stripe API connection successful");
            $this->line("   Account ID: " . $account->id);
            $this->line("   Country: " . $account->country);
            $this->line("   Charges Enabled: " . ($account->charges_enabled ? 'Yes' : 'No'));
            $this->line("   Payouts Enabled: " . ($account->payouts_enabled ? 'Yes' : 'No'));
        } catch (\Exception $e) {
            $this->error("   ❌ Stripe API connection failed: " . $e->getMessage());
            return 1;
        }
        
        // Test 5: Test Stripe Identity (if enabled)
        if ($stripeIdentityEnabled) {
            $this->info("\n5. Testing Stripe Identity:");
            try {
                $user = User::first();
                if (!$user) {
                    $this->warn("   ⚠️  No users found in database, skipping Identity test");
                } else {
                    $result = $stripeService->createIdentityVerificationSession($user);
                    if ($result['success']) {
                        $this->info("   ✅ Stripe Identity test successful");
                        $this->line("   Session ID: " . $result['session_id']);
                    } else {
                        $this->error("   ❌ Stripe Identity test failed: " . $result['error']);
                    }
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Stripe Identity test failed: " . $e->getMessage());
            }
        } else {
            $this->warn("\n5. Stripe Identity is disabled, skipping test");
        }
        
        $this->info("\n✅ Stripe configuration test completed!");
        return 0;
    }
}