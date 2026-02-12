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
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('checkout_id')->nullable();
            $table->string('order_number');
            $table->string('status')->default('pending');
            $table->string('financial_status')->default('pending');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('currency')->default('EUR');
            $table->integer('subtotal_amount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('shipping_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->text('shipping_address_json')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('discount_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->unique(['store_id', 'order_number']);
            $table->index('store_id');
            $table->index('customer_id');
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'financial_status']);
            $table->index(['store_id', 'fulfillment_status']);
            $table->index(['store_id', 'placed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
