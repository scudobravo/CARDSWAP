<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['open', 'locked'])->default('open');
            $table->foreignId('last_sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_email_notification_at')->nullable();
            $table->unsignedInteger('unread_count_buyer')->default(0);
            $table->unsignedInteger('unread_count_seller')->default(0);
            $table->timestamps();

            $table->unique(['order_id', 'buyer_id', 'seller_id']);
            $table->index(['buyer_id', 'last_message_at']);
            $table->index(['seller_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_conversations');
    }
};


