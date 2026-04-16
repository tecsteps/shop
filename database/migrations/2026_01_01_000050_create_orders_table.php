<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('open');
            $table->string('financial_status')->default('pending');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('currency', 3)->default('USD');
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

            $table->index(['store_id', 'status'], 'idx_orders_store_status');
            $table->index(['store_id', 'financial_status'], 'idx_orders_store_financial');
            $table->index(['store_id', 'placed_at'], 'idx_orders_store_placed_at');
            $table->index(['store_id', 'customer_id'], 'idx_orders_store_customer');
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('title_snapshot');
            $table->string('sku_snapshot')->nullable();
            $table->integer('quantity');
            $table->integer('unit_price_amount');
            $table->integer('total_amount');
            $table->text('tax_lines_json')->default('[]');
            $table->text('discount_allocations_json')->default('[]');

            $table->index('order_id', 'idx_order_lines_order_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('mock');
            $table->string('method');
            $table->string('provider_payment_id')->nullable();
            $table->string('status')->default('pending');
            $table->integer('amount');
            $table->string('currency', 3)->default('USD');
            $table->text('raw_json_encrypted')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_payments_order_id');
            $table->index('status', 'idx_payments_status');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('amount');
            $table->string('reason')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider_refund_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_refunds_order_id');
        });

        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('tracking_company')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_fulfillments_order_id');
        });

        Schema::create('fulfillment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');

            $table->unique(['fulfillment_id', 'order_line_id'], 'idx_fulfillment_lines_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_lines');
        Schema::dropIfExists('fulfillments');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
