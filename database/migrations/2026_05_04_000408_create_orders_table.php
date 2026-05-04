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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checkout_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->enum('payment_method', ['credit_card', 'paypal', 'bank_transfer']);
            $table->enum('status', ['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])->default('pending');
            $table->enum('financial_status', ['pending', 'authorized', 'paid', 'partially_refunded', 'refunded', 'voided'])->default('pending');
            $table->enum('fulfillment_status', ['unfulfilled', 'partial', 'fulfilled'])->default('unfulfilled');
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

            $table->unique(['store_id', 'order_number']);
            $table->unique('checkout_id');
            $table->index('store_id');
            $table->index('customer_id');
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'financial_status']);
            $table->index(['store_id', 'fulfillment_status']);
            $table->index(['store_id', 'placed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
