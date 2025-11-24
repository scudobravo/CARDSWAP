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
        Schema::table('card_listings', function (Blueprint $table) {
            // Modifica card_condition_score da decimal a string per supportare "AUTH"
            if (Schema::hasColumn('card_listings', 'card_condition_score')) {
                // Prima convertiamo i valori decimali esistenti in stringhe
                DB::statement("ALTER TABLE card_listings MODIFY COLUMN card_condition_score VARCHAR(10) NULL");
            }
            
            // Modifica autograph_condition_score da decimal a string per supportare "AUTH"
            if (Schema::hasColumn('card_listings', 'autograph_condition_score')) {
                // Prima convertiamo i valori decimali esistenti in stringhe
                DB::statement("ALTER TABLE card_listings MODIFY COLUMN autograph_condition_score VARCHAR(10) NULL");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_listings', function (Blueprint $table) {
            // Ripristina a decimal (nota: questo potrebbe causare perdita di dati se ci sono valori "AUTH")
            if (Schema::hasColumn('card_listings', 'card_condition_score')) {
                // Rimuovi eventuali valori "AUTH" prima di riconvertire
                DB::statement("UPDATE card_listings SET card_condition_score = NULL WHERE card_condition_score = 'AUTH'");
                DB::statement("ALTER TABLE card_listings MODIFY COLUMN card_condition_score DECIMAL(3,1) NULL");
            }
            
            if (Schema::hasColumn('card_listings', 'autograph_condition_score')) {
                // Rimuovi eventuali valori "AUTH" prima di riconvertire
                DB::statement("UPDATE card_listings SET autograph_condition_score = NULL WHERE autograph_condition_score = 'AUTH'");
                DB::statement("ALTER TABLE card_listings MODIFY COLUMN autograph_condition_score DECIMAL(3,1) NULL");
            }
        });
    }
};

