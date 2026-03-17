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
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->text('title_snapshot');
            $table->text('sku_snapshot')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->text('tax_lines_json')->default('[]');
            $table->text('discount_allocations_json')->default('[]');

            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();

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
