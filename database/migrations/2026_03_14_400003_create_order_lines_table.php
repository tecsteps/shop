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
            $table->integer('product_id')->nullable();
            $table->integer('variant_id')->nullable();
            $table->string('title_snapshot');
            $table->string('variant_title_snapshot')->nullable();
            $table->string('sku_snapshot')->nullable();
            $table->integer('quantity');
            $table->integer('unit_price_amount');
            $table->integer('subtotal_amount');
            $table->integer('total_amount');
            $table->integer('requires_shipping')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
