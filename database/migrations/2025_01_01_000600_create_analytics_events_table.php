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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->text('session_id')->nullable();
            $table->text('event_type');
            $table->text('properties_json')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('store_id', 'idx_analytics_events_store_id');
            $table->index(['store_id', 'event_type'], 'idx_analytics_events_store_type');
            $table->index(['store_id', 'created_at'], 'idx_analytics_events_store_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
