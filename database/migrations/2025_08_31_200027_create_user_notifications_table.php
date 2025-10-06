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
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // order_status, kyc_update, new_message, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Dati aggiuntivi per la notifica
            $table->string('action_url')->nullable(); // URL per azione
            $table->string('action_text')->nullable(); // Testo per azione
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Scadenza notifica
            $table->timestamps();
            
            // Indici per performance
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
