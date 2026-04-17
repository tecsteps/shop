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
            $table->text('order_number');
            $table->text('payment_method');
            $table->text('status')->default('pending');
            $table->text('financial_status')->default('pending');
            $table->text('fulfillment_status')->default('unfulfilled');
            $table->text('currency')->default('USD');
            $table->integer('subtotal_amount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('shipping_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->text('email')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->text('placed_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'order_number'], 'idx_orders_store_order_number');
            $table->index('store_id', 'idx_orders_store_id');
            $table->index('customer_id', 'idx_orders_customer_id');
            $table->index(['store_id', 'status'], 'idx_orders_store_status');
            $table->index(['store_id', 'financial_status'], 'idx_orders_store_financial');
            $table->index(['store_id', 'fulfillment_status'], 'idx_orders_store_fulfillment');
            $table->index(['store_id', 'placed_at'], 'idx_orders_placed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
