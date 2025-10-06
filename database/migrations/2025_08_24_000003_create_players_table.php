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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('position')->nullable(); // Attaccante, Centrocampista, Difensore, Portiere
            $table->string('nationality')->nullable();
            $table->string('photo_url')->nullable();
            $table->date('birth_date')->nullable();
            $table->integer('height_cm')->nullable();
            $table->string('preferred_foot')->nullable(); // Destro, Sinistro, Ambidestro
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['team_id', 'is_active']);
            $table->index(['position', 'is_active']);
            $table->index(['nationality', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
