<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rendi condition nullable per supportare carte graduate (che usano card_condition_score invece)
        // Rimuovi il default 'excellent' quando c'è grading
        Schema::table('card_listings', function (Blueprint $table) {
            if (Schema::hasColumn('card_listings', 'condition')) {
                // Modifica la colonna per renderla nullable e rimuovere il default
                DB::statement("ALTER TABLE card_listings MODIFY COLUMN `condition` ENUM('mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor', 'fair', 'very_good') NULL");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_listings', function (Blueprint $table) {
            if (Schema::hasColumn('card_listings', 'condition')) {
                // Ripristina il default 'excellent'
                DB::statement("ALTER TABLE card_listings MODIFY COLUMN `condition` ENUM('mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor', 'fair', 'very_good') DEFAULT 'excellent'");
            }
        });
    }
};

