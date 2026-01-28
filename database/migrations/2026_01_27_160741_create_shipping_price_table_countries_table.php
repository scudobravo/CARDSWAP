<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabella di associazione tra tabelle prezzi e paesi.
     * 
     * VINCOLO CRITICO: UNIQUE (seller_id, country_code)
     * - Lo stesso paese NON può essere associato a più tabelle dello stesso venditore
     * - Garantisce che ogni paese abbia una sola tabella prezzi attiva per venditore
     * 
     * Il campo seller_id è ridondante ma necessario per il vincolo UNIQUE,
     * che deve essere a livello di venditore (non solo a livello di tabella).
     */
    public function up(): void
    {
        Schema::create('shipping_price_table_countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_price_table_id')
                  ->constrained('shipping_price_tables')
                  ->onDelete('cascade');
            $table->foreignId('seller_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->char('country_code', 2);
            $table->timestamps();
            
            // VINCOLO CRITICO: stesso paese non può essere in due tabelle dello stesso seller
            $table->unique(['seller_id', 'country_code'], 'unique_seller_country');
            
            // Indici per performance
            $table->index('shipping_price_table_id');
            $table->index('seller_id');
            $table->index('country_code');
            $table->index(['seller_id', 'country_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_price_table_countries');
    }
};
