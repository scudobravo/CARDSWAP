<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabella principale per le tabelle prezzi di spedizione dei venditori.
     * Ogni venditore può creare fino a 4 tabelle prezzi (limite enforced a livello applicativo).
     * Ogni tabella può essere associata a uno o più paesi tramite shipping_price_table_countries.
     */
    public function up(): void
    {
        Schema::create('shipping_price_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            
            // Indici per performance
            $table->index('seller_id');
            $table->index(['seller_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_price_tables');
    }
};
