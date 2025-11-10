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
            // Aggiungi listing_type per distinguere tra single, bulk, sealed-pack, sealed-box, lot
            if (!Schema::hasColumn('card_listings', 'listing_type')) {
                $table->enum('listing_type', ['single', 'bulk', 'sealed-pack', 'sealed-box', 'lot'])
                      ->default('single')
                      ->after('card_model_id');
            }
            
            // Aggiungi title per i lot (se non esiste già)
            if (!Schema::hasColumn('card_listings', 'title')) {
                $table->string('title')->nullable()->after('listing_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_listings', function (Blueprint $table) {
            if (Schema::hasColumn('card_listings', 'listing_type')) {
                $table->dropColumn('listing_type');
            }
            if (Schema::hasColumn('card_listings', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};
