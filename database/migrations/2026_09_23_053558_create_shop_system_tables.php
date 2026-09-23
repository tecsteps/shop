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
            $table->string('default_currency', 3)->default('USD');
            $table->string('default_locale', 12)->default('en');
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
            $table->timestamp('created_at')->nullable();
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
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('weight_g')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['product_id', 'position']);
        });

        Schema::create('variant_option_values', function (Blueprint $table): void {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['variant_id', 'product_option_value_id']);
        });

        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->unique()->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity_on_hand')->default(0);
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->string('policy')->default('deny');
        });

        Schema::create('collections', function (Blueprint $table): void {
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

        Schema::create('collection_products', function (Blueprint $table): void {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['collection_id', 'product_id']);
        });

        Schema::create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('image');
            $table->string('storage_key');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default('processing');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('name')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->unique(['store_id', 'email']);
        });

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->json('address_json')->default('{}');
            $table->boolean('is_default')->default(false);
            $table->index(['customer_id', 'is_default']);
        });

        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('cart_version')->default(1);
            $table->string('status')->default('active');
            $table->string('discount_code')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price_amount')->default(0);
            $table->unsignedInteger('line_subtotal_amount')->default(0);
            $table->unsignedInteger('line_discount_amount')->default(0);
            $table->unsignedInteger('line_total_amount')->default(0);
            $table->unique(['cart_id', 'variant_id']);
        });

        Schema::create('shipping_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('countries')->default('[]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('flat');
            $table->unsignedInteger('price_amount')->default(0);
            $table->unsignedInteger('min_order_amount')->nullable();
            $table->unsignedInteger('max_order_amount')->nullable();
            $table->json('config_json')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('prices_include_tax')->default(false);
            $table->unsignedInteger('default_rate')->default(0);
            $table->json('rates_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('title');
            $table->string('type')->default('percentage');
            $table->unsignedInteger('value')->default(0);
            $table->unsignedInteger('minimum_subtotal_amount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('rules_json')->default('{}');
            $table->timestamps();
            $table->unique(['store_id', 'code']);
        });

        Schema::create('checkouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('started');
            $table->string('payment_method')->nullable();
            $table->string('email')->nullable();
            $table->json('shipping_address_json')->nullable();
            $table->json('billing_address_json')->nullable();
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_rates')->nullOnDelete();
            $table->string('discount_code')->nullable();
            $table->json('totals_json')->nullable();
            $table->unsignedInteger('subtotal_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('shipping_amount')->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checkout_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->string('payment_method');
            $table->string('status')->default('pending');
            $table->string('financial_status')->default('pending');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('subtotal_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('shipping_amount')->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->string('email')->nullable();
            $table->json('billing_address_json')->nullable();
            $table->json('shipping_address_json')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'order_number']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('title_snapshot');
            $table->string('variant_title_snapshot')->nullable();
            $table->string('sku_snapshot')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price_amount')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->json('tax_lines_json')->default('[]');
            $table->json('discount_allocations_json')->default('[]');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('mock');
            $table->string('method');
            $table->string('provider_payment_id')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('amount')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('raw_json_encrypted')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('reason')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider_refund_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('refund_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('amount')->default(0);
            $table->unique(['refund_id', 'order_line_id']);
        });

        Schema::create('fulfillments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('tracking_company')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('fulfillment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unique(['fulfillment_id', 'order_line_id']);
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->longText('body_html')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'handle']);
        });

        Schema::create('navigation_menus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('handle');
            $table->string('title');
            $table->timestamps();
            $table->unique(['store_id', 'handle']);
        });

        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->string('type')->default('link');
            $table->string('label');
            $table->string('url')->nullable();
            $table->unsignedInteger('resource_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('themes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('theme_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->longText('content');
            $table->timestamps();
            $table->unique(['theme_id', 'path']);
        });

        Schema::create('theme_settings', function (Blueprint $table): void {
            $table->foreignId('theme_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('settings_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('session_id')->nullable();
            $table->string('client_event_id')->nullable();
            $table->json('payload')->default('{}');
            $table->timestamp('created_at')->nullable();
            $table->index(['store_id', 'type', 'created_at']);
            $table->unique(['store_id', 'client_event_id']);
        });

        Schema::create('analytics_daily', function (Blueprint $table): void {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('revenue_amount')->default(0);
            $table->unsignedInteger('aov_amount')->default(0);
            $table->unsignedInteger('visits_count')->default(0);
            $table->unsignedInteger('add_to_cart_count')->default(0);
            $table->unsignedInteger('checkout_started_count')->default(0);
            $table->primary(['store_id', 'date']);
        });

        Schema::create('search_queries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('query');
            $table->unsignedInteger('results_count')->default(0);
            $table->string('session_id')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'query']);
        });

        Schema::create('search_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('synonyms_json')->default('{}');
            $table->json('stopwords_json')->default('[]');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('apps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->text('description')->nullable();
            $table->json('scopes_json')->default('[]');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('app_installations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->json('settings_json')->default('{}');
            $table->timestamps();
            $table->unique(['store_id', 'app_id']);
        });

        Schema::create('oauth_clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('client_id')->unique();
            $table->string('client_secret_hash');
            $table->json('scopes_json')->default('[]');
            $table->timestamps();
        });

        Schema::create('oauth_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('oauth_client_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash')->unique();
            $table->json('scopes_json')->default('[]');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('target_url');
            $table->text('secret');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->string('event_id');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->json('payload')->default('{}');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table): void {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->primary(['store_id', 'email']);
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement("CREATE VIRTUAL TABLE products_fts USING fts5(store_id UNINDEXED, product_id UNINDEXED, title, description, vendor, tags, tokenize='unicode61')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([
            'products_fts', 'customer_password_reset_tokens', 'webhook_deliveries', 'webhook_subscriptions', 'personal_access_tokens',
            'oauth_tokens', 'oauth_clients', 'app_installations', 'apps', 'search_settings', 'search_queries', 'analytics_daily', 'analytics_events', 'fulfillment_lines', 'fulfillments', 'refund_lines', 'refunds',
            'payments', 'order_lines', 'orders', 'checkouts', 'discounts', 'tax_settings', 'shipping_rates',
            'shipping_zones', 'cart_lines', 'carts', 'customer_addresses', 'customers', 'pages', 'theme_settings',
            'theme_files', 'themes', 'navigation_items', 'navigation_menus', 'product_media', 'collection_products', 'collections',
            'inventory_items', 'variant_option_values', 'product_variants', 'product_option_values', 'product_options',
            'products', 'store_settings', 'store_users', 'store_domains', 'stores', 'organizations',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
