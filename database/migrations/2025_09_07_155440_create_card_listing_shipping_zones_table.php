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
        Schema::create('card_listing_shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_listing_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipping_zone_id')->constrained()->onDelete('cascade');
            $table->decimal('shipping_cost', 8, 2)->nullable(); // Costo specifico per questa inserzione
            $table->integer('delivery_days_min')->nullable(); // Giorni minimi specifici
            $table->integer('delivery_days_max')->nullable(); // Giorni massimi specifici
            $table->timestamps();
            
            // Indici per performance
            $table->index(['card_listing_id']);
            $table->index(['shipping_zone_id']);
            $table->unique(['card_listing_id', 'shipping_zone_id'], 'clsz_unique'); // Evita duplicati
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_listing_shipping_zones');
    }
};
