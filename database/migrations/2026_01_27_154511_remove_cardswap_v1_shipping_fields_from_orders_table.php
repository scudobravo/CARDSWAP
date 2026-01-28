<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Rimuove i campi shipping_method, package_bucket, logistic_units_total
     * dalla tabella orders.
     * 
     * Questi campi sono stati spostati nella tabella order_shippings
     * per supportare correttamente ordini multi-seller.
     * 
     * NOTA: Se i campi non esistono (migrazione precedente non eseguita),
     * questa migrazione viene ignorata silenziosamente.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Verifica se le colonne esistono prima di rimuoverle
            $columns = Schema::getColumnListing('orders');
            $columnsToDrop = [];
            
            if (in_array('shipping_method', $columns)) {
                $columnsToDrop[] = 'shipping_method';
            }
            if (in_array('package_bucket', $columns)) {
                $columnsToDrop[] = 'package_bucket';
            }
            if (in_array('logistic_units_total', $columns)) {
                $columnsToDrop[] = 'logistic_units_total';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_method')->nullable()->after('shipping_cost');
            $table->enum('package_bucket', [
                'LETTER',
                'PARCEL_S',
                'PARCEL_M',
                'PARCEL_L'
            ])->nullable()->after('shipping_method');
            $table->decimal('logistic_units_total', 8, 2)->nullable()->after('package_bucket');
        });
    }
};
