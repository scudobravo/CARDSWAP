<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge campi per il sistema CardSwap Shipping V1 alla tabella orders.
     * Questi campi sono nullable per mantenere backward compatibility con ordini esistenti.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Metodo di spedizione selezionato (es. TRACKED_STANDARD, TRACKED_INSURED)
            $table->string('shipping_method')->nullable()->after('shipping_cost');
            
            // Bucket del pacco (LETTER, PARCEL_S, PARCEL_M, PARCEL_L)
            $table->enum('package_bucket', [
                'LETTER',
                'PARCEL_S',
                'PARCEL_M',
                'PARCEL_L'
            ])->nullable()->after('shipping_method');
            
            // Unità logistiche totali calcolate (per tracciabilità e reporting)
            $table->decimal('logistic_units_total', 8, 2)->nullable()->after('package_bucket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_method',
                'package_bucket',
                'logistic_units_total'
            ]);
        });
    }
};
