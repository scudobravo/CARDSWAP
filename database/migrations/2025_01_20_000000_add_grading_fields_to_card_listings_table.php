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
            // Aggiungi grading company (riferimento alla tabella grading_companies)
            if (!Schema::hasColumn('card_listings', 'grading_company_id')) {
                $table->foreignId('grading_company_id')->nullable()->after('autograph_condition')->constrained('grading_companies')->onDelete('set null');
            }
            
            // Aggiungi score numerici per condizione carta e autografo (0-10 con incrementi 0.5)
            if (!Schema::hasColumn('card_listings', 'card_condition_score')) {
                $table->decimal('card_condition_score', 3, 1)->nullable()->after('grading_company_id');
            }
            
            if (!Schema::hasColumn('card_listings', 'autograph_condition_score')) {
                $table->decimal('autograph_condition_score', 3, 1)->nullable()->after('card_condition_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_listings', function (Blueprint $table) {
            if (Schema::hasColumn('card_listings', 'autograph_condition_score')) {
                $table->dropColumn('autograph_condition_score');
            }
            if (Schema::hasColumn('card_listings', 'card_condition_score')) {
                $table->dropColumn('card_condition_score');
            }
            if (Schema::hasColumn('card_listings', 'grading_company_id')) {
                $table->dropForeign(['grading_company_id']);
                $table->dropColumn('grading_company_id');
            }
        });
    }
};

