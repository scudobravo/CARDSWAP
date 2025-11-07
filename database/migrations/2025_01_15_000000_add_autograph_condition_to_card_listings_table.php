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
            if (!Schema::hasColumn('card_listings', 'autograph_condition')) {
                $table->enum('autograph_condition', ['mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor', 'fair', 'very_good'])->nullable()->after('condition');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_listings', function (Blueprint $table) {
            if (Schema::hasColumn('card_listings', 'autograph_condition')) {
                $table->dropColumn('autograph_condition');
            }
        });
    }
};

