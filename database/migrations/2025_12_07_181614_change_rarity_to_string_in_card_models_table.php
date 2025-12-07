<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cambia il campo rarity da enum a string per supportare valori arbitrari (es. "Cinderella 75th Story", "Base Tier 2")
     */
    public function up(): void
    {
        // Per MySQL/MariaDB, devo usare DB::statement per cambiare da enum a string
        DB::statement("ALTER TABLE card_models MODIFY COLUMN rarity VARCHAR(255) DEFAULT 'common'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristina l'enum originale
        DB::statement("ALTER TABLE card_models MODIFY COLUMN rarity ENUM('common', 'uncommon', 'rare', 'mythic', 'special') DEFAULT 'common'");
    }
};
