<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('session_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('properties_json')->default('{}');
            $table->string('client_event_id')->nullable();
            $table->string('occurred_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_analytics_events_store_id');
            $table->index(['store_id', 'type'], 'idx_analytics_events_store_type');
            $table->index(['store_id', 'created_at'], 'idx_analytics_events_store_created');
            $table->index('session_id', 'idx_analytics_events_session');
            $table->index('customer_id', 'idx_analytics_events_customer');
            $table->unique(['store_id', 'client_event_id'], 'idx_analytics_events_client_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
