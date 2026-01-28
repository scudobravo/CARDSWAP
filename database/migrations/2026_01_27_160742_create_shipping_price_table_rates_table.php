<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabella delle tariffe per ogni combinazione:
     * - Tabella prezzi
     * - Bucket pacco (LETTER, PARCEL_S, PARCEL_M, PARCEL_L)
     * - Metodo spedizione (UNTRACKED_STANDARD, TRACKED_STANDARD, TRACKED_EXPRESS)
     * 
     * Note:
     * - price_eur NULL = metodo non disponibile per questa combinazione
     * - UNTRACKED_STANDARD sarà valido solo per LETTER (validazione a livello applicativo)
     * - Ogni combinazione (table, bucket, method) deve essere unica
     */
    public function up(): void
    {
        Schema::create('shipping_price_table_rates', function (Blueprint $table) {
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
            $table->enum('shipping_method', [
                'UNTRACKED_STANDARD',
                'TRACKED_STANDARD',
                'TRACKED_EXPRESS'
            ]);
            $table->decimal('price_eur', 10, 2)->nullable();
            $table->timestamps();
            
            // Vincolo: ogni combinazione (table, bucket, method) deve essere unica
            $table->unique(
                ['shipping_price_table_id', 'package_bucket', 'shipping_method'],
                'unique_table_bucket_method'
            );
            
            // Indici per performance
            $table->index('shipping_price_table_id');
            $table->index(['package_bucket', 'shipping_method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_price_table_rates');
    }
};
