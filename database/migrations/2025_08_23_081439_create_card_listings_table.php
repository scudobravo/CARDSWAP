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
        Schema::create('card_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('card_model_id')->constrained()->onDelete('cascade');
            $table->enum('condition', ['mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor'])->default('excellent');
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_foil')->default(false);
            $table->boolean('is_signed')->default(false);
            $table->boolean('is_limited')->default(false);
            $table->enum('status', ['draft', 'pending_review', 'approved', 'active', 'paused', 'rejected', 'expired', 'sold', 'inactive'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index(['seller_id', 'status']);
            $table->index(['card_model_id', 'condition', 'price']);
            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_listings');
    }
};
