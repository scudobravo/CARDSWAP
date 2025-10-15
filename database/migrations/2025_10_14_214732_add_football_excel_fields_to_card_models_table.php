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
            // Cambia il campo year da integer a string per supportare formati come "1967/68"
            $table->string('year', 20)->change();
            
            // Aggiungi i nuovi campi per supportare la struttura Excel
            $table->string('rarity_variation')->nullable()->after('rarity');
            $table->boolean('is_on_card_auto')->default(false)->after('is_relic');
            $table->boolean('is_jewel')->default(false)->after('is_on_card_auto');
            $table->boolean('is_booklet')->default(false)->after('is_jewel');
            $table->boolean('is_multi_player_dual')->default(false)->after('is_booklet');
            $table->boolean('is_multi_player_triple')->default(false)->after('is_multi_player_dual');
            $table->boolean('is_multi_player_quad')->default(false)->after('is_multi_player_triple');
            
            // Aggiungi indici per performance
            $table->index(['rarity_variation', 'is_active']);
            $table->index(['is_on_card_auto', 'is_active']);
            $table->index(['is_jewel', 'is_active']);
            $table->index(['is_booklet', 'is_active']);
            $table->index(['is_multi_player_dual', 'is_active']);
            $table->index(['is_multi_player_triple', 'is_active']);
            $table->index(['is_multi_player_quad', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_models', function (Blueprint $table) {
            // Rimuovi gli indici
            $table->dropIndex(['rarity_variation', 'is_active']);
            $table->dropIndex(['is_on_card_auto', 'is_active']);
            $table->dropIndex(['is_jewel', 'is_active']);
            $table->dropIndex(['is_booklet', 'is_active']);
            $table->dropIndex(['is_multi_player_dual', 'is_active']);
            $table->dropIndex(['is_multi_player_triple', 'is_active']);
            $table->dropIndex(['is_multi_player_quad', 'is_active']);
            
            // Rimuovi i campi aggiunti
            $table->dropColumn([
                'rarity_variation',
                'is_on_card_auto',
                'is_jewel',
                'is_booklet',
                'is_multi_player_dual',
                'is_multi_player_triple',
                'is_multi_player_quad'
            ]);
            
            // Ripristina il campo year come integer
            $table->integer('year')->change();
        });
    }
};
