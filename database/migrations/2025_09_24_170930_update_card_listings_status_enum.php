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
        // Aggiungi il campo rejection_reason se non esiste
        if (!Schema::hasColumn('card_listings', 'rejection_reason')) {
            Schema::table('card_listings', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('published_at');
            });
        }
        
        // Rimuovi il campo user_id se esiste (ora usiamo seller_id)
        if (Schema::hasColumn('card_listings', 'user_id')) {
            Schema::table('card_listings', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
        
        // Aggiorna gli stati esistenti
        DB::table('card_listings')
            ->where('status', 'active')
            ->update(['status' => 'active']); // Mantieni active come active
        
        // Aggiorna l'enum per supportare i nuovi stati
        DB::statement("ALTER TABLE card_listings MODIFY COLUMN status ENUM('draft', 'pending_review', 'approved', 'active', 'paused', 'rejected', 'expired', 'sold', 'inactive') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristina l'enum originale
        DB::statement("ALTER TABLE card_listings MODIFY COLUMN status ENUM('draft', 'active', 'sold', 'inactive') DEFAULT 'draft'");
    }
};