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
        Schema::table('card_listings', function (Blueprint $table) {
            // Aggiungi card_set_id per sealed-pack/box/lot
            if (!Schema::hasColumn('card_listings', 'card_set_id')) {
                $table->foreignId('card_set_id')->nullable()->after('card_model_id')->constrained('card_sets')->onDelete('set null');
            }
            
            // Aggiungi year per sealed-pack/box/lot
            if (!Schema::hasColumn('card_listings', 'year')) {
                $table->string('year')->nullable()->after('card_set_id');
            }
            
            // Aggiungi brand per sealed-pack/box/lot
            if (!Schema::hasColumn('card_listings', 'brand')) {
                $table->string('brand')->nullable()->after('year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_listings', function (Blueprint $table) {
            if (Schema::hasColumn('card_listings', 'brand')) {
                $table->dropColumn('brand');
            }
            if (Schema::hasColumn('card_listings', 'year')) {
                $table->dropColumn('year');
            }
            if (Schema::hasColumn('card_listings', 'card_set_id')) {
                $table->dropForeign(['card_set_id']);
                $table->dropColumn('card_set_id');
            }
        });
    }
};
