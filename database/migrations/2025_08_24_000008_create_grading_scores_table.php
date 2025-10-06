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
        Schema::create('grading_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_company_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('score', 3, 1); // 10.0, 9.5, 9.0, etc.
            $table->string('description'); // "Gem Mint", "Near Mint", etc.
            $table->string('short_code')->nullable(); // "NM-MT", "EX-MT", etc.
            $table->boolean('is_special')->default(false); // Per voti speciali come "Black Label Pristine"
            $table->text('notes')->nullable(); // Note aggiuntive
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['grading_company_id', 'score', 'description']);
            $table->index(['score', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_scores');
    }
};
