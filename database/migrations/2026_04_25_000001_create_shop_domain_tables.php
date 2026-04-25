<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');

        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('billing_email')->index();
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('status')->default('active')->index();
            $table->string('default_currency', 3)->default('EUR');
            $table->string('default_locale')->default('en');
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });

        Schema::create('store_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('hostname')->unique();
            $table->string('type')->default('storefront');
            $table->boolean('is_primary')->default(false);
            $table->string('tls_mode')->default('managed');
            $table->timestamp('created_at')->nullable();
            $table->index(['store_id', 'is_primary']);
        });

        Schema::create('store_users', function (Blueprint $table): void {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('staff');
            $table->timestamps();
            $table->primary(['store_id', 'user_id']);
            $table->index(['store_id', 'role']);
        });

        Schema::create('store_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('settings_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('products', function (Blueprint $table): void {
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

        Schema::create('product_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->unique(['product_id', 'position']);
        });

        Schema::create('product_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('position')->default(0);
            $table->unique(['product_option_id', 'position']);
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable()->index();
            $table->unsignedInteger('price_amount')->default(0);
            $table->unsignedInteger('compare_at_amount')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('weight_g')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['product_id', 'position']);
            $table->index(['product_id', 'is_default']);
        });

        Schema::create('variant_option_values', function (Blueprint $table): void {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['variant_id', 'product_option_value_id'], 'variant_option_values_pk');
        });

        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->string('policy')->default('deny');
            $table->timestamps();
            $table->unique(['store_id', 'variant_id']);
        });

        Schema::create('collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description_html')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['store_id', 'handle']);
        });

        Schema::create('collection_products', function (Blueprint $table): void {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['collection_id', 'product_id']);
        });

        Schema::create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('themes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->json('settings_json')->default('{}');
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('body_html');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['store_id', 'handle']);
        });

        Schema::create('navigation_menus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->timestamps();
            $table->unique(['store_id', 'handle']);
        });

        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('navigation_menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->nullOnDelete();
            $table->string('label');
            $table->string('url');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name');
            $table->string('password_hash')->nullable();
            $table->boolean('accepts_marketing')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'email']);
        });

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('postal_code');
            $table->string('country_code', 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email');
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->primary(['store_id', 'email']);
        });

        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('cart_version')->default(1);
            $table->string('status')->default('active');
            $table->string('discount_code')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_amount');
            $table->json('snapshot_json')->default('{}');
            $table->timestamps();
            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('shipping_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('countries')->default('[]');
            $table->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('price_amount');
            $table->unsignedInteger('min_order_amount')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('prices_include_tax')->default(false);
            $table->unsignedInteger('default_rate_bps')->default(1900);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('type');
            $table->unsignedInteger('value_amount')->default(0);
            $table->unsignedInteger('value_bps')->default(0);
            $table->unsignedInteger('min_purchase_amount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['store_id', 'code']);
        });

        Schema::create('checkouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('started');
            $table->string('email')->nullable();
            $table->json('shipping_address_json')->nullable();
            $table->foreignId('shipping_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->json('totals_json')->default('{}');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->string('email');
            $table->string('status')->default('pending');
            $table->string('financial_status')->default('pending');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('subtotal_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('shipping_amount')->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->json('shipping_address_json')->nullable();
            $table->json('timeline_json')->default('[]');
            $table->timestamps();
            $table->unique(['store_id', 'order_number']);
        });

        Schema::create('order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('title');
            $table->string('sku')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_amount');
            $table->unsignedInteger('total_amount');
            $table->json('snapshot_json')->default('{}');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('mock');
            $table->string('method');
            $table->string('status');
            $table->unsignedInteger('amount');
            $table->string('reference')->nullable();
            $table->text('raw_payload_encrypted')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('reason')->nullable();
            $table->boolean('restocked')->default(false);
            $table->timestamps();
        });

        Schema::create('fulfillments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('created');
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fulfillment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
        });

        Schema::create('search_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('synonyms_json')->default('[]');
            $table->json('stopwords_json')->default('[]');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('search_queries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('query');
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload_json')->default('{}');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('analytics_daily', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('sales_amount')->default(0);
            $table->unique(['store_id', 'date']);
        });

        Schema::create('apps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->json('scopes')->default('[]');
            $table->timestamps();
        });

        Schema::create('app_installations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('installed');
            $table->timestamps();
        });

        Schema::create('webhook_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->string('endpoint_url');
            $table->string('secret');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('webhook_subscription_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->json('payload_json')->default('{}');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach ([
            'webhook_deliveries',
            'webhook_subscriptions',
            'app_installations',
            'apps',
            'analytics_daily',
            'analytics_events',
            'search_queries',
            'search_settings',
            'fulfillment_lines',
            'fulfillments',
            'refunds',
            'payments',
            'order_lines',
            'orders',
            'checkouts',
            'discounts',
            'tax_settings',
            'shipping_rates',
            'shipping_zones',
            'cart_lines',
            'carts',
            'customer_password_reset_tokens',
            'customer_addresses',
            'customers',
            'navigation_items',
            'navigation_menus',
            'pages',
            'themes',
            'product_media',
            'collection_products',
            'collections',
            'inventory_items',
            'variant_option_values',
            'product_variants',
            'product_option_values',
            'product_options',
            'products',
            'store_settings',
            'store_users',
            'store_domains',
            'stores',
            'organizations',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

