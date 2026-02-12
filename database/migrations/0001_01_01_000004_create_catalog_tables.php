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
            $table->json('tags')->default('[]');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'published_at']);
            $table->index(['store_id', 'vendor']);
            $table->index(['store_id', 'product_type']);
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);

            $table->unique(['product_id', 'position']);
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('position')->default(0);

            $table->unique(['product_option_id', 'position']);
        });

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
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('sku');
            $table->index('barcode');
            $table->index(['product_id', 'position']);
            $table->index(['product_id', 'is_default']);
        });

        Schema::create('variant_option_values', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();

            $table->primary(['variant_id', 'product_option_value_id']);
            $table->index('product_option_value_id');
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->unique()->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->string('policy')->default('deny');
            $table->timestamps();

            $table->index('store_id');
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description_html')->nullable();
            $table->string('type')->default('manual');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['store_id', 'handle']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('collection_products', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);

            $table->primary(['collection_id', 'product_id']);
            $table->index('product_id');
            $table->index(['collection_id', 'position']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('image');
            $table->string('storage_key');
            $table->string('alt_text')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('byte_size')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default('processing');
            $table->timestamps();

            $table->index('product_id');
            $table->index(['product_id', 'position']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('collection_products');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('variant_option_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('products');
    }
};
