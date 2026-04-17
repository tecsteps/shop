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
            $table->string('type');
            $table->string('session_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->text('properties_json')->default('{}');
            $table->string('client_event_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('store_id', 'idx_analytics_events_store_id');
            $table->index(['store_id', 'type'], 'idx_analytics_events_store_type');
            $table->index(['store_id', 'created_at'], 'idx_analytics_events_store_created');
            $table->index('session_id', 'idx_analytics_events_session');
            $table->index('customer_id', 'idx_analytics_events_customer');
            $table->unique(['store_id', 'client_event_id'], 'idx_analytics_events_client_event');
        });

        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('date');
            $table->integer('orders_count')->default(0);
            $table->integer('revenue_amount')->default(0);
            $table->integer('aov_amount')->default(0);
            $table->integer('visits_count')->default(0);
            $table->integer('add_to_cart_count')->default(0);
            $table->integer('checkout_started_count')->default(0);
            $table->integer('checkout_completed_count')->default(0);

            $table->primary(['store_id', 'date']);
            $table->index(['store_id', 'date'], 'idx_analytics_daily_store_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily');
        Schema::dropIfExists('analytics_events');
    }
};
