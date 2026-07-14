<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('password_hash')->nullable();
            $table->string('name')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamps();
            $table->unique(['store_id', 'email'], 'idx_customers_store_email');
            $table->index('store_id', 'idx_customers_store_id');
        });

        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('version')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index('store_id', 'idx_themes_store_id');
            $table->index(['store_id', 'status'], 'idx_themes_store_status');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('body_html')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'handle'], 'idx_pages_store_handle');
            $table->index('store_id', 'idx_pages_store_id');
            $table->index(['store_id', 'status'], 'idx_pages_store_status');
        });

        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('handle');
            $table->string('title');
            $table->timestamps();
            $table->unique(['store_id', 'handle'], 'idx_navigation_menus_store_handle');
            $table->index('store_id', 'idx_navigation_menus_store_id');
        });

        Schema::create('search_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->primary();
            $table->text('synonyms_json')->default('[]');
            $table->text('stop_words_json')->default('[]');
            $table->timestamp('updated_at')->nullable();
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('countries_json')->default('[]');
            $table->text('regions_json')->default('[]');
            $table->index('store_id', 'idx_shipping_zones_store_id');
        });

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->primary();
            $table->enum('mode', ['manual', 'provider'])->default('manual');
            $table->enum('provider', ['stripe_tax', 'none'])->default('none');
            $table->boolean('prices_include_tax')->default(false);
            $table->text('config_json')->default('{}');
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });

        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['code', 'automatic'])->default('code');
            $table->string('code')->nullable();
            $table->enum('value_type', ['fixed', 'percent', 'free_shipping']);
            $table->integer('value_amount')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->text('rules_json')->default('{}');
            $table->enum('status', ['draft', 'active', 'expired', 'disabled'])->default('active');
            $table->timestamps();
            $table->unique(['store_id', 'code'], 'idx_discounts_store_code');
            $table->index('store_id', 'idx_discounts_store_id');
            $table->index(['store_id', 'status'], 'idx_discounts_store_status');
            $table->index(['store_id', 'type'], 'idx_discounts_store_type');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
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

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description_html')->nullable();
            $table->enum('type', ['manual', 'automated'])->default('manual');
            $table->enum('status', ['draft', 'active', 'archived'])->default('active');
            $table->timestamps();
            $table->unique(['store_id', 'handle'], 'idx_collections_store_handle');
            $table->index('store_id', 'idx_collections_store_id');
            $table->index(['store_id', 'status'], 'idx_collections_store_status');
        });

        Schema::create('app_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->text('scopes_json')->default('[]');
            $table->enum('status', ['active', 'suspended', 'uninstalled'])->default('active');
            $table->timestamp('installed_at')->nullable();
            $table->unique(['store_id', 'app_id'], 'idx_app_installations_store_app');
            $table->index('store_id', 'idx_app_installations_store_id');
            $table->index('app_id', 'idx_app_installations_app_id');
        });

        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->string('client_id');
            $table->text('client_secret_encrypted');
            $table->text('redirect_uris_json')->default('[]');
            $table->unique('client_id', 'idx_oauth_clients_client_id');
            $table->index('app_id', 'idx_oauth_clients_app_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
        Schema::dropIfExists('app_installations');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('products');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('search_settings');
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('themes');
        Schema::dropIfExists('customers');
    }
};
