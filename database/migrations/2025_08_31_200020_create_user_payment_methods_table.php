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
        Schema::create('user_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit_card', 'debit_card', 'paypal', 'bank_transfer']);
            $table->string('name'); // Nome del metodo (es. "Carta principale")
            $table->string('last_four_digits')->nullable(); // Ultime 4 cifre carta
            $table->string('card_brand')->nullable(); // Visa, Mastercard, etc.
            $table->date('expiry_date')->nullable(); // Data scadenza carta
            $table->string('account_email')->nullable(); // Per PayPal
            $table->string('bank_name')->nullable(); // Per bonifici
            $table->string('iban')->nullable(); // Per bonifici
            $table->string('bic_swift')->nullable(); // Per bonifici internazionali
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            // Indici per performance
            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payment_methods');
    }
};
