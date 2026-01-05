<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aggiorna l'ENUM con i nuovi stati per il sistema di trattenuta fondi
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending', 
            'pending_payment', 
            'paid_funds_held', 
            'label_created', 
            'in_transit_verified', 
            'delivered_pending_72h', 
            'dispute_hold', 
            'completed', 
            'confirmed', 
            'shipped', 
            'delivered', 
            'cancelled', 
            'refunded'
        ) DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristina lo stato precedente
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending', 
            'pending_payment', 
            'confirmed', 
            'shipped', 
            'delivered', 
            'cancelled', 
            'refunded'
        ) DEFAULT 'pending'");
    }
};

