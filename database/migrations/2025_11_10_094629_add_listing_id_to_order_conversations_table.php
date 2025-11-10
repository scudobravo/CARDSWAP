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
        Schema::table('order_conversations', function (Blueprint $table) {
            // Rimuovi il foreign key constraint esistente
            $table->dropForeign(['order_id']);
            
            // Rimuovi l'unique constraint esistente
            $table->dropUnique(['order_id', 'buyer_id', 'seller_id']);
        });

        // Modifica order_id per renderlo nullable
        Schema::table('order_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
        });

        // Riaggiungi il foreign key e aggiungi listing_id
        Schema::table('order_conversations', function (Blueprint $table) {
            // Riaggiungi foreign key per order_id
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            
            // Aggiungi listing_id per supportare conversazioni basate su prodotti
            $table->unsignedBigInteger('listing_id')->nullable()->after('order_id');
            $table->foreign('listing_id')->references('id')->on('card_listings')->onDelete('cascade');
            
            // Aggiungi unique constraints
            $table->unique(['order_id', 'buyer_id', 'seller_id'], 'order_conversations_order_unique');
            $table->unique(['listing_id', 'buyer_id', 'seller_id'], 'order_conversations_listing_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_conversations', function (Blueprint $table) {
            // Rimuovi listing_id
            $table->dropForeign(['listing_id']);
            $table->dropColumn('listing_id');
            
            // Rimuovi unique constraints
            $table->dropUnique('order_conversations_order_unique');
            $table->dropUnique('order_conversations_listing_unique');
            
            // Rimuovi foreign key per order_id
            $table->dropForeign(['order_id']);
        });

        // Ripristina order_id come non nullable
        Schema::table('order_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
        });

        // Riaggiungi foreign key e unique constraint originale
        Schema::table('order_conversations', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->unique(['order_id', 'buyer_id', 'seller_id']);
        });
    }
};
