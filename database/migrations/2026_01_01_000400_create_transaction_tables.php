<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->integer('position')->default(0);
            $table->index('product_option_id', 'idx_product_option_values_option_id');
            $table->unique(['product_option_id', 'position'], 'idx_product_option_values_option_position');
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->enum('policy', ['deny', 'continue'])->default('deny');
            $table->unique('variant_id', 'idx_inventory_items_variant_id');
            $table->index('store_id', 'idx_inventory_items_store_id');
        });

        Schema::create('cart_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_amount')->default(0);
            $table->integer('line_subtotal_amount')->default(0);
            $table->integer('line_discount_amount')->default(0);
            $table->integer('line_total_amount')->default(0);
            $table->index('cart_id', 'idx_cart_lines_cart_id');
            $table->unique(['cart_id', 'variant_id'], 'idx_cart_lines_cart_variant');
        });

        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['started', 'addressed', 'shipping_selected', 'payment_selected', 'completed', 'expired'])->default('started');
            $table->enum('payment_method', ['credit_card', 'paypal', 'bank_transfer'])->nullable();
            $table->string('email')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->string('discount_code')->nullable();
            $table->text('tax_provider_snapshot_json')->nullable();
            $table->text('totals_json')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('store_id', 'idx_checkouts_store_id');
            $table->index('cart_id', 'idx_checkouts_cart_id');
            $table->index('customer_id', 'idx_checkouts_customer_id');
            $table->index(['store_id', 'status'], 'idx_checkouts_status');
            $table->index('expires_at', 'idx_checkouts_expires_at');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->enum('payment_method', ['credit_card', 'paypal', 'bank_transfer']);
            $table->enum('status', ['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])->default('pending');
            $table->enum('financial_status', ['pending', 'authorized', 'paid', 'partially_refunded', 'refunded', 'voided'])->default('pending');
            $table->enum('fulfillment_status', ['unfulfilled', 'partial', 'fulfilled'])->default('unfulfilled');
            $table->string('currency')->default('USD');
            $table->integer('subtotal_amount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('shipping_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->string('email')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'order_number'], 'idx_orders_store_order_number');
            $table->index('store_id', 'idx_orders_store_id');
            $table->index('customer_id', 'idx_orders_customer_id');
            $table->index(['store_id', 'status'], 'idx_orders_store_status');
            $table->index(['store_id', 'financial_status'], 'idx_orders_store_financial');
            $table->index(['store_id', 'fulfillment_status'], 'idx_orders_store_fulfillment');
            $table->index(['store_id', 'placed_at'], 'idx_orders_placed_at');
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('session_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
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
            $table->index(['store_id', 'date'], 'idx_analytics_daily_store_date');
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->string('event_id');
            $table->integer('attempt_count')->default(1);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamp('last_attempt_at')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('response_body_snippet')->nullable();
            $table->index('subscription_id', 'idx_webhook_deliveries_subscription_id');
            $table->index('event_id', 'idx_webhook_deliveries_event_id');
            $table->index('status', 'idx_webhook_deliveries_status');
            $table->index('last_attempt_at', 'idx_webhook_deliveries_last_attempt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('analytics_daily');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('checkouts');
        Schema::dropIfExists('cart_lines');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('product_option_values');
    }
};
