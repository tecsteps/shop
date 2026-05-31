<?php

use App\Support\Database\SqliteEnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            // The checkout this order was created from. Used to keep
            // completeCheckout idempotent (a duplicate submit returns this order).
            $table->foreignId('checkout_id')->nullable()->constrained('checkouts')->nullOnDelete();
            $table->string('order_number');
            $table->string('payment_method');
            $table->string('status')->default('pending');
            $table->string('financial_status')->default('pending');
            $table->string('fulfillment_status')->default('unfulfilled');
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

        SqliteEnumCheck::add('orders', 'payment_method', ['credit_card', 'paypal', 'bank_transfer']);
        SqliteEnumCheck::add('orders', 'status', ['pending', 'paid', 'fulfilled', 'cancelled', 'refunded']);
        SqliteEnumCheck::add('orders', 'financial_status', [
            'pending', 'authorized', 'paid', 'partially_refunded', 'refunded', 'voided',
        ]);
        SqliteEnumCheck::add('orders', 'fulfillment_status', ['unfulfilled', 'partial', 'fulfilled']);
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
