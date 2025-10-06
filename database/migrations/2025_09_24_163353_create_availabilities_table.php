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
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_listing_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Utente che ha il lock
            $table->enum('status', ['available', 'locked', 'reserved', 'sold'])->default('available');
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_locked')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_sold')->default(0);
            $table->timestamp('locked_until')->nullable(); // Scadenza del lock
            $table->timestamp('reserved_until')->nullable(); // Scadenza della prenotazione
            $table->json('lock_metadata')->nullable(); // Dati aggiuntivi del lock
            $table->timestamps();
            
            // Indici per performance
            $table->index(['card_listing_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['locked_until']);
            $table->index(['reserved_until']);
            $table->index(['status', 'quantity_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
