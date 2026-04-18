<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->unsignedInteger('price_amount')->default(0);
            $table->unsignedInteger('compare_at_amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('weight_g')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('product_id', 'idx_product_variants_product_id');
            $table->index('sku', 'idx_product_variants_sku');
            $table->index('barcode', 'idx_product_variants_barcode');
            $table->index(['product_id', 'position'], 'idx_product_variants_product_position');
            $table->index(['product_id', 'is_default'], 'idx_product_variants_product_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
