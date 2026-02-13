<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_amount')->default(0);
            $table->integer('line_subtotal_amount')->default(0);
            $table->integer('line_discount_amount')->default(0);
            $table->integer('line_total_amount')->default(0);

            $table->index('cart_id', 'idx_cart_lines_cart_id');
            $table->unique(['cart_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_lines');
    }
};
