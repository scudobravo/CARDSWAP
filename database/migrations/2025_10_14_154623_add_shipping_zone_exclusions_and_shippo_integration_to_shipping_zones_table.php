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
            // Nuovi campi per zone geografiche
            $table->string('zone_type')->default('country')->after('country_code'); // 'worldwide', 'continent', 'country', 'region'
            $table->json('included_countries')->nullable()->after('zone_type'); // Paesi inclusi nella zona
            $table->json('excluded_countries')->nullable()->after('included_countries'); // Paesi esclusi dalla zona
            $table->json('included_regions')->nullable()->after('excluded_countries'); // Regioni incluse
            $table->json('excluded_regions')->nullable()->after('included_regions'); // Regioni escluse
            
            // Integrazione SHIPPO
            $table->boolean('use_shippo_pricing')->default(false)->after('excluded_regions'); // Usa prezzi SHIPPO
            $table->string('shippo_carrier')->nullable()->after('use_shippo_pricing'); // Corriere preferito
            $table->string('shippo_service_type')->nullable()->after('shippo_carrier'); // Tipo servizio (standard, express, insured)
            $table->decimal('shippo_markup', 8, 2)->default(1.60)->after('shippo_service_type'); // Markup da aggiungere
            $table->boolean('shippo_require_insurance')->default(false)->after('shippo_markup'); // Richiedi assicurazione
            
            // Campi per gestione avanzata
            $table->boolean('is_worldwide')->default(false)->after('shippo_require_insurance'); // Spedizione mondiale
            $table->text('description')->nullable()->after('is_worldwide'); // Descrizione zona
            $table->integer('sort_order')->default(0)->after('description'); // Ordine di visualizzazione
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->dropColumn([
                'zone_type',
                'included_countries',
                'excluded_countries', 
                'included_regions',
                'excluded_regions',
                'use_shippo_pricing',
                'shippo_carrier',
                'shippo_service_type',
                'shippo_markup',
                'shippo_require_insurance',
                'is_worldwide',
                'description',
                'sort_order'
            ]);
        });
    }
};