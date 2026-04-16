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
            $table->string('session_id', 64)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->text('properties_json')->default('{}');
            $table->string('client_event_id', 64)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['store_id', 'type', 'occurred_at'], 'idx_analytics_events_type_time');
            $table->unique('client_event_id', 'idx_analytics_events_client_id');
        });

        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('orders_count')->default(0);
            $table->integer('revenue_amount')->default(0);
            $table->integer('aov_amount')->default(0);
            $table->integer('visits_count')->default(0);
            $table->integer('add_to_cart_count')->default(0);
            $table->integer('checkout_started_count')->default(0);
            $table->integer('checkout_completed_count')->default(0);

            $table->primary(['store_id', 'date']);
        });

        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index(['store_id', 'created_at'], 'idx_search_queries_store_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('analytics_daily');
        Schema::dropIfExists('analytics_events');
    }
};
