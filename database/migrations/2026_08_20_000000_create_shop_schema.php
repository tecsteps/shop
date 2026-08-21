<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('billing_email')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('status')->default('active')->index();
            $table->string('default_currency', 3)->default('EUR');
            $table->string('default_locale', 10)->default('en');
            $table->string('timezone')->default('UTC');
            $table->string('primary_domain')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('store_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('hostname')->unique();
            $table->string('type')->default('storefront');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index(['store_id', 'is_primary']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('status')->default('active')->index();
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('store_users', function (Blueprint $table): void {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('staff');
            $table->timestamps();
            $table->primary(['store_id', 'user_id']);
            $table->index('user_id');
            $table->index(['store_id', 'role']);
        });

        Schema::create('store_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('general_json')->nullable();
            $table->json('checkout_json')->nullable();
            $table->json('notification_json')->nullable();
            $table->json('social_json')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->string('vendor')->nullable()->index();
            $table->string('product_type')->nullable()->index();
            $table->json('tags')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sales_count')->default(0);
            $table->json('metadata')->nullable();
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
            $table->timestamps();
            $table->unique(['product_id', 'position']);
        });

        Schema::create('product_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['product_option_id', 'position']);
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Default');
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable()->index();
            $table->unsignedInteger('price_amount');
            $table->unsignedInteger('compare_at_amount')->nullable();
            $table->unsignedInteger('cost_amount')->nullable();
            $table->unsignedInteger('weight_grams')->default(0);
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'position']);
            $table->index(['product_id', 'is_default']);
        });

        Schema::create('variant_option_values', function (Blueprint $table): void {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['variant_id', 'product_option_value_id']);
            $table->index('product_option_value_id');
        });

        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->unique()->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->string('policy')->default('deny');
            $table->timestamps();
            $table->index('store_id');
        });

        Schema::create('collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('image_url')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'handle']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('collection_products', function (Blueprint $table): void {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['collection_id', 'product_id']);
            $table->index('product_id');
            $table->index(['collection_id', 'position']);
        });

        Schema::create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('url')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('status')->default('ready');
            $table->unsignedInteger('position')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'position']);
            $table->index('status');
        });

        Schema::create('themes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft')->index();
            $table->string('version')->default('1.0.0');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'status']);
        });

        Schema::create('theme_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->longText('content')->nullable();
            $table->timestamps();
            $table->unique(['theme_id', 'path']);
        });

        Schema::create('theme_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['theme_id', 'key']);
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->longText('content')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'handle']);
        });

        Schema::create('navigation_menus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('handle');
            $table->timestamps();
            $table->unique(['store_id', 'handle']);
        });

        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('navigation_menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->string('label');
            $table->string('type')->default('link');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->nullOnDelete();
            $table->timestamps();
            $table->index(['navigation_menu_id', 'position']);
        });

        Schema::create('search_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('synonyms')->nullable();
            $table->json('stopwords')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('search_queries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('query');
            $table->unsignedInteger('results_count')->default(0);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['store_id', 'created_at']);
            $table->index(['store_id', 'query']);
        });

        Schema::create('shipping_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('countries_json')->nullable();
            $table->json('regions_json')->nullable();
            $table->timestamps();
            $table->index('store_id');
        });

        Schema::create('shipping_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('flat');
            $table->unsignedInteger('price_amount')->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->json('config_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('estimated_days_min')->nullable();
            $table->unsignedInteger('estimated_days_max')->nullable();
            $table->timestamps();
            $table->index(['shipping_zone_id', 'is_active']);
        });

        Schema::create('tax_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('mode')->default('manual');
            $table->unsignedInteger('default_rate_basis_points')->default(0);
            $table->json('rates_json')->nullable();
            $table->json('provider_config_json')->nullable();
            $table->timestamps();
        });

        Schema::create('discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('type')->default('code');
            $table->string('value_type')->default('percent');
            $table->unsignedInteger('value_amount')->default(0);
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('rules_json')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'code']);
            $table->index(['store_id', 'type']);
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('password_hash')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'email']);
            $table->index('store_id');
        });

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->json('address_json');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['customer_id', 'is_default']);
        });

        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('cart_version')->default(1);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->index('store_id');
            $table->index('customer_id');
        });

        Schema::create('cart_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_amount');
            $table->unsignedInteger('line_subtotal_amount');
            $table->unsignedInteger('line_discount_amount')->default(0);
            $table->unsignedInteger('line_total_amount');
            $table->timestamps();
            $table->unique(['cart_id', 'variant_id']);
            $table->index('cart_id');
        });

        Schema::create('checkouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('started')->index();
            $table->string('email');
            $table->string('payment_method')->nullable();
            $table->json('shipping_address_json')->nullable();
            $table->json('billing_address_json')->nullable();
            $table->foreignId('shipping_rate_id')->nullable()->constrained('shipping_rates')->nullOnDelete();
            $table->string('discount_code')->nullable();
            $table->json('totals_json')->nullable();
            $table->json('tax_provider_snapshot_json')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->index('cart_id');
            $table->index('customer_id');
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkout_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('pending')->index();
            $table->string('financial_status')->default('pending')->index();
            $table->string('fulfillment_status')->default('unfulfilled')->index();
            $table->string('payment_method')->nullable();
            $table->string('email');
            $table->json('shipping_address_json')->nullable();
            $table->json('billing_address_json')->nullable();
            $table->unsignedInteger('subtotal_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('shipping_amount')->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->timestamp('placed_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'order_number']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'financial_status']);
            $table->index(['store_id', 'fulfillment_status']);
        });

        Schema::create('order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_title');
            $table->string('variant_title')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_amount');
            $table->unsignedInteger('line_subtotal_amount');
            $table->unsignedInteger('line_discount_amount')->default(0);
            $table->unsignedInteger('line_total_amount');
            $table->json('tax_lines_json')->nullable();
            $table->json('discount_allocations_json')->nullable();
            $table->timestamps();
            $table->index('order_id');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('mock');
            $table->string('provider_payment_id')->nullable();
            $table->string('method');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('amount');
            $table->text('raw_json_encrypted')->nullable();
            $table->timestamps();
            $table->index(['provider', 'provider_payment_id']);
            $table->index('order_id');
        });

        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->boolean('restock')->default(false);
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('fulfillments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('tracking_company')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('tracking_url')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            $table->index(['tracking_company', 'tracking_number']);
        });

        Schema::create('fulfillment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['fulfillment_id', 'order_line_id']);
        });

        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('session_id')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_event_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'client_event_id']);
            $table->index(['store_id', 'type']);
            $table->index(['store_id', 'created_at']);
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

        Schema::create('apps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->json('scopes')->nullable();
            $table->timestamps();
        });

        Schema::create('app_installations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->json('config')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'app_id']);
        });

        Schema::create('oauth_clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('client_id')->unique();
            $table->text('client_secret_encrypted');
            $table->text('redirect_uris');
            $table->timestamps();
        });

        Schema::create('oauth_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('oauth_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_hash')->unique();
            $table->json('scopes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->text('target_url');
            $table->text('secret_encrypted');
            $table->string('status')->default('active');
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamps();
            $table->index(['store_id', 'event']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('webhook_subscription_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'webhook_deliveries', 'webhook_subscriptions', 'oauth_tokens', 'oauth_clients',
            'app_installations', 'apps', 'analytics_daily', 'analytics_events',
            'fulfillment_lines', 'fulfillments', 'refunds', 'payments', 'order_lines', 'orders',
            'checkouts', 'cart_lines', 'carts', 'discounts', 'tax_settings', 'shipping_rates',
            'shipping_zones', 'search_queries', 'search_settings', 'navigation_items',
            'navigation_menus', 'pages', 'theme_settings', 'theme_files', 'themes',
            'product_media', 'collection_products', 'collections', 'inventory_items',
            'variant_option_values', 'product_variants', 'product_option_values', 'product_options',
            'products', 'customer_addresses', 'customers', 'store_settings', 'store_users',
            'store_domains', 'stores', 'organizations',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['status', 'last_login_at']);
        });
    }
};
