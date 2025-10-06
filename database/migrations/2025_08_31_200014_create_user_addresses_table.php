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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label')->default('Indirizzo principale'); // Casa, Ufficio, etc.
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state_province')->nullable();
            $table->string('postal_code');
            $table->string('country');
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_billing')->default(false);
            $table->boolean('is_shipping')->default(true);
            $table->timestamps();
            
            // Indici per performance
            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'is_shipping']);
            $table->index(['user_id', 'is_billing']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
