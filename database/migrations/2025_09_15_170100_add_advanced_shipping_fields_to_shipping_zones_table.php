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
        Schema::table('shipping_zones', function (Blueprint $table) {
            // Campi per calcolo avanzato costi
            $table->decimal('base_cost', 8, 2)->default(0)->after('shipping_cost');
            $table->decimal('cost_per_kg', 8, 2)->default(0)->after('base_cost');
            $table->decimal('cost_per_euro', 8, 2)->default(0)->after('cost_per_kg');
            $table->decimal('free_shipping_threshold', 8, 2)->nullable()->after('cost_per_euro');
            $table->decimal('max_weight_kg', 8, 2)->nullable()->after('free_shipping_threshold');
            $table->decimal('max_value_euro', 8, 2)->nullable()->after('max_weight_kg');
            
            // Campi per limitazioni venditore
            $table->boolean('requires_seller_approval')->default(false)->after('max_value_euro');
            $table->json('allowed_seller_roles')->nullable()->after('requires_seller_approval');
            $table->integer('min_seller_rating')->nullable()->after('allowed_seller_roles');
            $table->integer('min_seller_sales')->nullable()->after('min_seller_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->dropColumn([
                'base_cost',
                'cost_per_kg', 
                'cost_per_euro',
                'free_shipping_threshold',
                'max_weight_kg',
                'max_value_euro',
                'requires_seller_approval',
                'allowed_seller_roles',
                'min_seller_rating',
                'min_seller_sales'
            ]);
        });
    }
};