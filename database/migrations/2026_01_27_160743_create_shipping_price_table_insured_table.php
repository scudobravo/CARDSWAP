<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabella per la configurazione dell'assicurazione per ogni combinazione:
     * - Tabella prezzi
     * - Bucket pacco (LETTER, PARCEL_S, PARCEL_M, PARCEL_L)
     * 
     * Se enabled = true, l'assicurazione è disponibile per questo bucket nella tabella.
     * Il costo dell'assicurazione viene calcolato a livello applicativo usando:
     * - config('shipping.insurance_rate')
     * - config('shipping.insurance_min_fee_eur')
     * 
     * Ogni combinazione (table, bucket) deve essere unica.
     */
    public function up(): void
    {
        Schema::create('shipping_price_table_insured', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_price_table_id')
                  ->constrained('shipping_price_tables')
                  ->onDelete('cascade');
            $table->enum('package_bucket', [
                'LETTER',
                'PARCEL_S',
                'PARCEL_M',
                'PARCEL_L'
            ]);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
            
            // Vincolo: ogni combinazione (table, bucket) deve essere unica
            $table->unique(
                ['shipping_price_table_id', 'package_bucket'],
                'unique_table_bucket_insured'
            );
            
            // Indici per performance
            $table->index('shipping_price_table_id');
            $table->index('package_bucket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_price_table_insured');
    }
};
