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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->string('status')->default('draft');
            $table->text('description_html')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->text('tags')->default('[]');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_products_store_handle');
            $table->index('store_id', 'idx_products_store_id');
            $table->index(['store_id', 'status'], 'idx_products_store_status');
            $table->index(['store_id', 'published_at'], 'idx_products_published_at');
            $table->index(['store_id', 'vendor'], 'idx_products_vendor');
            $table->index(['store_id', 'product_type'], 'idx_products_product_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
