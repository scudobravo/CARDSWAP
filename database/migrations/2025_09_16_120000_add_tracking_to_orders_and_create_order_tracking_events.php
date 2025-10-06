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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('carrier_code')->nullable()->after('tracking_number');
            $table->string('tracking_url')->nullable()->after('carrier_code');
            $table->timestamp('last_shipment_reminder_at')->nullable()->after('delivered_at');
        });

        Schema::create('order_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('status')->nullable();
            $table->string('carrier_code')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_tracking_events');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['carrier_code', 'tracking_url', 'last_shipment_reminder_at']);
        });
    }
};


