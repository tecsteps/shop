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
            $table->text('email');
            $table->text('phone')->nullable();
            $table->text('status')->default('pending');
            $table->text('financial_status')->default('pending');
            $table->text('fulfillment_status')->default('unfulfilled');
            $table->text('currency')->default('USD');
            $table->integer('subtotal')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('shipping_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total')->default(0);
            $table->json('shipping_address_json')->nullable();
            $table->json('billing_address_json')->nullable();
            $table->text('discount_code')->nullable();
            $table->text('payment_method')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->json('totals_json')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
