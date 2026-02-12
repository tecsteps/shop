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
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->string('payment_method');
            $table->string('status')->default('pending');
            $table->string('financial_status')->default('pending');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('currency', 3)->default('USD');
            $table->integer('subtotal_amount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('shipping_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->string('email')->nullable();
            $table->json('billing_address_json')->nullable();
            $table->json('shipping_address_json')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'order_number']);
            $table->index('store_id');
            $table->index('customer_id');
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'financial_status']);
            $table->index(['store_id', 'fulfillment_status']);
            $table->index(['store_id', 'placed_at']);
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('title_snapshot');
            $table->string('sku_snapshot')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->json('tax_lines_json')->default('[]');
            $table->json('discount_allocations_json')->default('[]');
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
            $table->index('variant_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('mock');
            $table->string('method');
            $table->string('provider_payment_id')->nullable();
            $table->string('status')->default('pending');
            $table->integer('amount')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('raw_json_encrypted')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index(['provider', 'provider_payment_id']);
            $table->index('method');
            $table->index('status');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->integer('amount')->default(0);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider_refund_id')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('payment_id');
            $table->index('status');
        });

        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('tracking_company')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            $table->index(['tracking_company', 'tracking_number']);
        });

        Schema::create('fulfillment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['fulfillment_id', 'order_line_id']);
            $table->index('fulfillment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
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
