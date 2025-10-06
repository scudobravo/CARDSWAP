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
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('card_model_id')->constrained()->onDelete('cascade');
            $table->decimal('max_price', 10, 2)->nullable();
            $table->enum('condition_preference', ['any', 'mint', 'near_mint', 'excellent', 'good'])->default('any');
            $table->boolean('notify_on_match')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'card_model_id']);
            $table->index(['user_id']);
            $table->index(['card_model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
