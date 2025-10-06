<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Campi Stripe Connect
            if (!Schema::hasColumn('users', 'stripe_account_id')) {
                $table->string('stripe_account_id')->nullable()->after('two_factor_enabled');
            }
            if (!Schema::hasColumn('users', 'stripe_charges_enabled')) {
                $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_account_id');
            }
            if (!Schema::hasColumn('users', 'stripe_payouts_enabled')) {
                $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_charges_enabled');
            }
            if (!Schema::hasColumn('users', 'stripe_details_submitted')) {
                $table->boolean('stripe_details_submitted')->default(false)->after('stripe_payouts_enabled');
            }
            
            // Campi Stripe Identity
            if (!Schema::hasColumn('users', 'stripe_verification_session_id')) {
                $table->string('stripe_verification_session_id')->nullable()->after('stripe_details_submitted');
            }
            if (!Schema::hasColumn('users', 'stripe_identity_verified')) {
                $table->boolean('stripe_identity_verified')->default(false)->after('stripe_verification_session_id');
            }
            if (!Schema::hasColumn('users', 'stripe_identity_verified_at')) {
                $table->timestamp('stripe_identity_verified_at')->nullable()->after('stripe_identity_verified');
            }
            
            // Indici per performance
            if (!Schema::hasIndex('users', 'users_stripe_account_id_index')) {
                $table->index(['stripe_account_id'], 'users_stripe_account_id_index');
            }
            if (!Schema::hasIndex('users', 'users_stripe_identity_verified_index')) {
                $table->index(['stripe_identity_verified'], 'users_stripe_identity_verified_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rimuovi campi Stripe
            $columns = [
                'stripe_account_id', 'stripe_charges_enabled', 'stripe_payouts_enabled',
                'stripe_details_submitted', 'stripe_verification_session_id',
                'stripe_identity_verified', 'stripe_identity_verified_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Rimuovi indici
            if (Schema::hasIndex('users', 'users_stripe_account_id_index')) {
                $table->dropIndex('users_stripe_account_id_index');
            }
            if (Schema::hasIndex('users', 'users_stripe_identity_verified_index')) {
                $table->dropIndex('users_stripe_identity_verified_index');
            }
        });
    }
};