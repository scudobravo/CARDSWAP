<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabella order_shippings per salvare i dati di spedizione CardSwap V1
     * per ogni seller in un ordine multi-seller.
     * 
     * Un ordine può avere più record (uno per ogni seller).
     */
    public function up(): void
    {
        Schema::create('order_shippings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            
            // Metodo di spedizione selezionato (es. TRACKED_STANDARD, TRACKED_INSURED)
            $table->string('shipping_method')->nullable();
            
            // Bucket del pacco (LETTER, PARCEL_S, PARCEL_M, PARCEL_L)
            $table->enum('package_bucket', [
                'LETTER',
                'PARCEL_S',
                'PARCEL_M',
                'PARCEL_L'
            ])->nullable();
            
            // Unità logistiche totali calcolate (per tracciabilità e reporting)
            $table->decimal('logistic_units_total', 8, 2)->nullable();
            
            // Prezzo base spedizione (senza assicurazione)
            $table->decimal('shipping_price', 8, 2)->nullable();
            
            // Costo assicurazione (se applicabile)
            $table->decimal('insurance_fee', 8, 2)->default(0.00);
            
            $table->timestamps();
            
            // Indici
            $table->index(['order_id', 'seller_id']);
            $table->index('seller_id');
            $table->index('shipping_method');
            $table->index('package_bucket');
            
            // Unique constraint: un seller può avere solo un record di spedizione per ordine
            $table->unique(['order_id', 'seller_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shippings');
    }
};
