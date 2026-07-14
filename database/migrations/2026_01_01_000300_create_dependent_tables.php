<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('position')->default(0);
            $table->index('product_id', 'idx_product_options_product_id');
            $table->unique(['product_id', 'position'], 'idx_product_options_product_position');
        });

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
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
            $table->index('product_id', 'idx_product_variants_product_id');
            $table->index('sku', 'idx_product_variants_sku');
            $table->index('barcode', 'idx_product_variants_barcode');
            $table->index(['product_id', 'position'], 'idx_product_variants_product_position');
            $table->index(['product_id', 'is_default'], 'idx_product_variants_product_default');
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('storage_key');
            $table->string('alt_text')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('byte_size')->nullable();
            $table->integer('position')->default(0);
            $table->enum('status', ['processing', 'ready', 'failed'])->default('processing');
            $table->timestamp('created_at')->nullable();
            $table->index('product_id', 'idx_product_media_product_id');
            $table->index(['product_id', 'position'], 'idx_product_media_product_position');
            $table->index('status', 'idx_product_media_status');
        });

        Schema::create('collection_products', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->primary(['collection_id', 'product_id']);
            $table->index('product_id', 'idx_collection_products_product_id');
            $table->index(['collection_id', 'position'], 'idx_collection_products_position');
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->text('address_json')->default('{}');
            $table->boolean('is_default')->default(false);
            $table->index('customer_id', 'idx_customer_addresses_customer_id');
            $table->index(['customer_id', 'is_default'], 'idx_customer_addresses_default');
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency')->default('USD');
            $table->integer('cart_version')->default(1);
            $table->enum('status', ['active', 'converted', 'abandoned'])->default('active');
            $table->timestamps();
            $table->index('store_id', 'idx_carts_store_id');
            $table->index('customer_id', 'idx_carts_customer_id');
            $table->index(['store_id', 'status'], 'idx_carts_store_status');
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->enum('type', ['link', 'page', 'collection', 'product'])->default('link');
            $table->string('label');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->integer('position')->default(0);
            $table->index('menu_id', 'idx_navigation_items_menu_id');
            $table->index(['menu_id', 'position'], 'idx_navigation_items_menu_position');
        });

        Schema::create('theme_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('storage_key');
            $table->string('sha256');
            $table->integer('byte_size')->default(0);
            $table->unique(['theme_id', 'path'], 'idx_theme_files_theme_path');
            $table->index('theme_id', 'idx_theme_files_theme_id');
        });

        Schema::create('theme_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('theme_id')->primary();
            $table->text('settings_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
            $table->foreign('theme_id')->references('id')->on('themes')->cascadeOnDelete();
        });

        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->text('query');
            $table->text('filters_json')->nullable();
            $table->integer('results_count')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->index('store_id', 'idx_search_queries_store_id');
            $table->index(['store_id', 'created_at'], 'idx_search_queries_store_created');
            $table->index(['store_id', 'query'], 'idx_search_queries_store_query');
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['flat', 'weight', 'price', 'carrier'])->default('flat');
            $table->text('config_json')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->index('zone_id', 'idx_shipping_rates_zone_id');
            $table->index(['zone_id', 'is_active'], 'idx_shipping_rates_zone_active');
        });

        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained('app_installations')->cascadeOnDelete();
            $table->string('access_token_hash');
            $table->string('refresh_token_hash')->nullable();
            $table->timestamp('expires_at');
            $table->index('installation_id', 'idx_oauth_tokens_installation_id');
            $table->unique('access_token_hash', 'idx_oauth_tokens_access_hash');
            $table->index('expires_at', 'idx_oauth_tokens_expires_at');
        });

        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('target_url');
            $table->text('signing_secret_encrypted');
            $table->enum('status', ['active', 'paused', 'disabled'])->default('active');
            $table->index('store_id', 'idx_webhook_subscriptions_store_id');
            $table->index(['store_id', 'event_type'], 'idx_webhook_subscriptions_store_event');
            $table->index('app_installation_id', 'idx_webhook_subscriptions_installation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
        Schema::dropIfExists('oauth_tokens');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('theme_settings');
        Schema::dropIfExists('theme_files');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('collection_products');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_options');
    }
};
