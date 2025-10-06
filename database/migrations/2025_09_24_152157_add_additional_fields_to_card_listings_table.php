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
            // Aggiungi solo i campi che non esistono già
            if (!Schema::hasColumn('card_listings', 'language')) {
                $table->string('language', 20)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('card_listings', 'is_altered')) {
                $table->boolean('is_altered')->default(false)->after('is_signed');
            }
            if (!Schema::hasColumn('card_listings', 'is_first_edition')) {
                $table->boolean('is_first_edition')->default(false)->after('is_altered');
            }
            if (!Schema::hasColumn('card_listings', 'is_negotiable')) {
                $table->boolean('is_negotiable')->default(false)->after('is_first_edition');
            }
            // seller_id è già presente nella migrazione originale
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_listings', function (Blueprint $table) {
            $columns = ['language', 'is_altered', 'is_first_edition', 'is_negotiable'];
            $existingColumns = array_filter($columns, function($column) {
                return Schema::hasColumn('card_listings', $column);
            });
            
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
