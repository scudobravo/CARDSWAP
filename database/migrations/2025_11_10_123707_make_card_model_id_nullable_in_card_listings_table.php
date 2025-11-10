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
            // Rimuovi il foreign key constraint esistente
            $table->dropForeign(['card_model_id']);
            
            // Rendi card_model_id nullable
            $table->foreignId('card_model_id')->nullable()->change();
            
            // Ricrea il foreign key constraint come nullable
            $table->foreign('card_model_id')->references('id')->on('card_models')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_listings', function (Blueprint $table) {
            // Rimuovi il foreign key constraint
            $table->dropForeign(['card_model_id']);
            
            // Rendi card_model_id non nullable (attenzione: questo fallirà se ci sono valori null)
            $table->foreignId('card_model_id')->nullable(false)->change();
            
            // Ricrea il foreign key constraint
            $table->foreign('card_model_id')->references('id')->on('card_models')->onDelete('cascade');
        });
    }
};
