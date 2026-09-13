<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('price_amount')->default(0);
            $table->integer('compare_at_amount')->nullable();
            $table->string('currency')->default('USD');
            $table->integer('weight_g')->nullable();
            $table->integer('requires_shipping')->default(1);
            $table->integer('is_default')->default(0);
            $table->integer('position')->default(0);
            $table->string('status')->default('active');
            $table->index('product_id');
            $table->index('sku');
            $table->index('barcode');
            $table->index(['product_id', 'position']);
            $table->index(['product_id', 'is_default']);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
