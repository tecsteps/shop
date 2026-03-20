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
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->text('type');
            $table->text('session_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->text('properties_json')->default('{}');
            $table->text('client_event_id')->nullable();
            $table->text('occurred_at')->nullable();
            $table->text('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('created_at')->nullable();

            $table->index('store_id', 'idx_analytics_events_store_id');
            $table->index(['store_id', 'type'], 'idx_analytics_events_store_type');
            $table->index(['store_id', 'created_at'], 'idx_analytics_events_store_created');
            $table->index('session_id', 'idx_analytics_events_session');
            $table->index('customer_id', 'idx_analytics_events_customer');
            $table->unique(['store_id', 'client_event_id'], 'idx_analytics_events_client_event');
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
