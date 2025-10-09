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
        Schema::table('order_feedbacks', function (Blueprint $table) {
            $table->text('seller_response')->nullable()->after('comment');
            $table->timestamp('seller_response_at')->nullable()->after('seller_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_feedbacks', function (Blueprint $table) {
            $table->dropColumn(['seller_response', 'seller_response_at']);
        });
    }
};
