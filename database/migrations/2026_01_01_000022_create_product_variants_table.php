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
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('price_amount')->default(0);
            $table->integer('compare_at_amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('weight_g')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('position')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('sku', 'idx_product_variants_sku');
            $table->index('barcode', 'idx_product_variants_barcode');
            $table->index(['product_id', 'position'], 'idx_product_variants_product_position');
            $table->index(['product_id', 'is_default'], 'idx_product_variants_product_default');
        });

        Schema::create('variant_option_values', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();

            $table->primary(['variant_id', 'product_option_value_id']);
            $table->index('product_option_value_id', 'idx_variant_option_values_value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_option_values');
        Schema::dropIfExists('product_variants');
    }
};
