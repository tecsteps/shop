<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('USD');
            $table->integer('cart_version')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['store_id', 'customer_id'], 'idx_carts_store_customer');
            $table->index(['store_id', 'status'], 'idx_carts_store_status');
        });

        Schema::create('cart_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity');
            $table->integer('unit_price_amount');
            $table->integer('line_subtotal_amount')->default(0);
            $table->integer('line_discount_amount')->default(0);
            $table->integer('line_total_amount')->default(0);

            $table->unique(['cart_id', 'variant_id'], 'idx_cart_lines_cart_variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_lines');
        Schema::dropIfExists('carts');
    }
};
