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
            // Importo che il venditore dovrebbe ricevere (94% del subtotale)
            $table->decimal('seller_payout_amount', 10, 2)->nullable()->after('total_amount');
            
            // Stato del payout: pending_payout, paid, dispute_hold, cancelled
            $table->enum('payout_status', ['pending_payout', 'paid', 'dispute_hold', 'cancelled'])->nullable()->after('seller_payout_amount');
            
            // Data prevista per il payout (72h dopo consegna)
            $table->timestamp('payout_scheduled_at')->nullable()->after('payout_status');
            
            // Data effettiva del payout
            $table->timestamp('payout_completed_at')->nullable()->after('payout_scheduled_at');
            
            // ID del trasferimento Stripe (quando viene effettuato)
            $table->string('stripe_transfer_id')->nullable()->after('payout_completed_at');
            
            // Data creazione etichetta Shippo
            $table->timestamp('label_created_at')->nullable()->after('stripe_transfer_id');
            
            // Flag per dispute aperta
            $table->boolean('has_dispute')->default(false)->after('label_created_at');
            
            // Data apertura dispute
            $table->timestamp('dispute_opened_at')->nullable()->after('has_dispute');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'seller_payout_amount',
                'payout_status',
                'payout_scheduled_at',
                'payout_completed_at',
                'stripe_transfer_id',
                'label_created_at',
                'has_dispute',
                'dispute_opened_at'
            ]);
        });
    }
};

