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
            $table->boolean('is_autograph')->default(false)->after('is_legend');
            $table->boolean('is_relic')->default(false)->after('is_autograph');
            
            // Aggiungi indici per performance
            $table->index(['is_autograph', 'is_active']);
            $table->index(['is_relic', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_models', function (Blueprint $table) {
            $table->dropIndex(['is_autograph', 'is_active']);
            $table->dropIndex(['is_relic', 'is_active']);
            
            $table->dropColumn(['is_autograph', 'is_relic']);
        });
    }
};
