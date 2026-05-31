<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // store_id is denormalized here so catalog guards (which look up
            // order_lines by variant_id without a current_store bound) and
            // store-scoped reporting stay correct.
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
