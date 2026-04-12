<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('type');
            $table->string('session_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->text('properties_json')->nullable();
            $table->string('client_event_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['store_id', 'type', 'occurred_at'], 'idx_analytics_events_store_type_at');
            $table->index(['store_id', 'occurred_at'], 'idx_analytics_events_store_at');
            $table->index('session_id', 'idx_analytics_events_session');
            $table->index('customer_id', 'idx_analytics_events_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
