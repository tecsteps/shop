<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_option_values', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['variant_id', 'product_option_value_id']);
            $table->index('product_option_value_id', 'idx_variant_option_values_value_id');
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
            $table->text('tax_lines_json')->default('[]');
            $table->text('discount_allocations_json')->default('[]');
            $table->index('order_id', 'idx_order_lines_order_id');
            $table->index('product_id', 'idx_order_lines_product_id');
            $table->index('variant_id', 'idx_order_lines_variant_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('provider', ['mock'])->default('mock');
            $table->enum('method', ['credit_card', 'paypal', 'bank_transfer']);
            $table->string('provider_payment_id')->nullable();
            $table->enum('status', ['pending', 'captured', 'failed', 'refunded'])->default('pending');
            $table->integer('amount')->default(0);
            $table->string('currency')->default('USD');
            $table->text('raw_json_encrypted')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('order_id', 'idx_payments_order_id');
            $table->index(['provider', 'provider_payment_id'], 'idx_payments_provider_id');
            $table->index('method', 'idx_payments_method');
            $table->index('status', 'idx_payments_status');
        });

        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'shipped', 'delivered'])->default('pending');
            $table->string('tracking_company')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('order_id', 'idx_fulfillments_order_id');
            $table->index('status', 'idx_fulfillments_status');
            $table->index(['tracking_company', 'tracking_number'], 'idx_fulfillments_tracking');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('variant_option_values');
    }
};
