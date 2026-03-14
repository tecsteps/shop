<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->text('title')->nullable();
            $table->text('sku')->nullable();
            $table->text('barcode')->nullable();
            $table->integer('price_amount')->default(0);
            $table->integer('compare_at_price_amount')->nullable();
            $table->integer('cost_amount')->nullable();
            $table->integer('weight_grams')->nullable();
            $table->integer('requires_shipping')->default(1);
            $table->integer('is_default')->default(0);
            $table->text('status')->default('active');
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
