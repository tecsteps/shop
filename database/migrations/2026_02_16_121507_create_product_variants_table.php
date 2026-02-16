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
            $table->string('currency')->default('USD');
            $table->integer('weight_g')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('position')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('sku');
            $table->index('barcode');
            $table->index(['product_id', 'position']);
            $table->index(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
