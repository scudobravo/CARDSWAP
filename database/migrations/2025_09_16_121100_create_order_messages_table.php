<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('order_conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->boolean('is_read_by_buyer')->default(false);
            $table->boolean('is_read_by_seller')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('flagged_reason', 255)->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_messages');
    }
};


