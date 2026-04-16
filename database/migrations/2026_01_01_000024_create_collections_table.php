<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description_html')->nullable();
            $table->string('type')->default('manual');
            $table->text('rules_json')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_collections_store_handle');
            $table->index(['store_id', 'status'], 'idx_collections_store_status');
        });

        Schema::create('collection_products', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);

            $table->primary(['collection_id', 'product_id']);
            $table->index('product_id', 'idx_collection_products_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_products');
        Schema::dropIfExists('collections');
    }
};
