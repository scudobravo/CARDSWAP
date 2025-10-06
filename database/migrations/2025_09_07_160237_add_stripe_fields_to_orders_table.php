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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->after('notes');
            $table->timestamp('paid_at')->nullable()->after('stripe_payment_intent_id');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->text('refund_reason')->nullable()->after('refunded_at');
            
            $table->index(['stripe_payment_intent_id']);
            $table->index(['paid_at']);
            $table->index(['refunded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['stripe_payment_intent_id']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['refunded_at']);
            
            $table->dropColumn([
                'stripe_payment_intent_id',
                'paid_at',
                'refunded_at',
                'refund_reason'
            ]);
        });
    }
};
