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
        Schema::table('card_models', function (Blueprint $table) {
            // Aggiungo campi per entità calcio
            $table->foreignId('card_set_id')->nullable()->after('category_id')->constrained()->onDelete('set null');
            $table->foreignId('player_id')->nullable()->after('card_set_id')->constrained()->onDelete('set null');
            $table->foreignId('team_id')->nullable()->after('player_id')->constrained()->onDelete('set null');
            $table->foreignId('league_id')->nullable()->after('team_id')->constrained()->onDelete('set null');
            
            // Aggiungo campi specifici per carte calcio
            $table->string('card_number_in_set')->nullable()->after('card_number'); // Numero nel set
            $table->string('parallel_type')->nullable()->after('card_number_in_set'); // Tipo parallelo (Gold, Silver, etc.)
            $table->string('insert_type')->nullable()->after('parallel_type'); // Tipo insert (Rookie, Star, etc.)
            $table->boolean('is_rookie')->default(false)->after('insert_type'); // È una rookie card?
            $table->boolean('is_star')->default(false)->after('is_rookie'); // È una star card?
            $table->boolean('is_legend')->default(false)->after('is_star'); // È una legend card?
            
            // Aggiungo campi per grading
            $table->foreignId('grading_company_id')->nullable()->after('is_legend')->constrained()->onDelete('set null');
            $table->decimal('grading_score', 3, 1)->nullable()->after('grading_company_id'); // Voto grading (es. 9.5)
            $table->string('grading_notes')->nullable()->after('grading_score'); // Note aggiuntive grading
            
            // Aggiungo indici per i nuovi campi
            $table->index(['card_set_id', 'card_number_in_set']);
            $table->index(['player_id', 'is_active']);
            $table->index(['team_id', 'is_active']);
            $table->index(['league_id', 'is_active']);
            $table->index(['grading_company_id', 'grading_score']);
            $table->index(['is_rookie', 'is_active']);
            $table->index(['is_star', 'is_active']);
            $table->index(['is_legend', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_models', function (Blueprint $table) {
            // Rimuovo indici
            $table->dropIndex(['card_set_id', 'card_number_in_set']);
            $table->dropIndex(['player_id', 'is_active']);
            $table->dropIndex(['team_id', 'is_active']);
            $table->dropIndex(['league_id', 'is_active']);
            $table->dropIndex(['grading_company_id', 'grading_score']);
            $table->dropIndex(['is_rookie', 'is_active']);
            $table->dropIndex(['is_star', 'is_active']);
            $table->dropIndex(['is_legend', 'is_active']);
            
            // Rimuovo colonne
            $table->dropForeign(['card_set_id']);
            $table->dropForeign(['player_id']);
            $table->dropForeign(['team_id']);
            $table->dropForeign(['league_id']);
            $table->dropForeign(['grading_company_id']);
            
            $table->dropColumn([
                'card_set_id',
                'player_id', 
                'team_id',
                'league_id',
                'card_number_in_set',
                'parallel_type',
                'insert_type',
                'is_rookie',
                'is_star',
                'is_legend',
                'grading_company_id',
                'grading_score',
                'grading_notes'
            ]);
        });
    }
};
