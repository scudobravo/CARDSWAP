<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned(); // 1-5 stelle
            $table->text('comment')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_hidden')->default(false); // per moderazione
            $table->timestamps();

            $table->unique(['order_id', 'buyer_id', 'seller_id']); // un feedback per ordine-venditore
            $table->index(['seller_id', 'rating']);
            $table->index(['buyer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_feedbacks');
    }
};
