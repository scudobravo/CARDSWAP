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
        // Modifico la colonna condition per usare i valori corretti dal CSV
        // Uso backticks perché 'condition' è una parola riservata in MySQL
        DB::statement("ALTER TABLE card_listings MODIFY COLUMN `condition` ENUM('mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor', 'fair', 'very_good') DEFAULT 'excellent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristino i valori originali
        DB::statement("ALTER TABLE card_listings MODIFY COLUMN `condition` ENUM('mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor') DEFAULT 'excellent'");
    }
};
