<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->text('title');
            $table->text('handle');
            $table->text('description_html')->nullable();
            $table->text('status')->default('draft');
            $table->text('vendor')->nullable();
            $table->text('product_type')->nullable();
            $table->text('tags')->nullable();
            $table->text('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_products_store_id_handle');
            $table->index(['store_id', 'status'], 'idx_products_store_id_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
