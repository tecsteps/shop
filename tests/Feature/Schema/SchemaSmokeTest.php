<?php

use App\Enums\CheckoutStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Enums\UserStatus;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerPasswordResetToken;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\InventoryItem;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\OauthClient;
use App\Models\OauthToken;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\SearchQuery;
use App\Models\SearchSetting;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSetting;
use App\Models\StoreUser;
use App\Models\TaxSetting;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Models\VariantOptionValue;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('has every table from spec 01', function () {
    $tables = [
        'organizations', 'stores', 'store_domains', 'users', 'store_users', 'store_settings',
        'products', 'product_options', 'product_option_values', 'product_variants',
        'variant_option_values', 'inventory_items', 'collections', 'collection_products', 'product_media',
        'themes', 'theme_files', 'theme_settings', 'pages', 'navigation_menus', 'navigation_items',
        'search_settings', 'search_queries',
        'carts', 'cart_lines', 'checkouts', 'shipping_zones', 'shipping_rates', 'tax_settings', 'discounts',
        'customers', 'customer_addresses', 'orders', 'order_lines', 'payments', 'refunds',
        'fulfillments', 'fulfillment_lines',
        'analytics_events', 'analytics_daily',
        'apps', 'app_installations', 'oauth_clients', 'oauth_tokens',
        'webhook_subscriptions', 'webhook_deliveries',
        'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
        'personal_access_tokens', 'password_reset_tokens', 'customer_password_reset_tokens',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});

it('has the spec columns on the fortify users table', function () {
    expect(Schema::hasColumn('users', 'status'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'last_login_at'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'password'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'two_factor_secret'))->toBeTrue();
});

it('creates a record from every factory', function (string $modelClass) {
    $model = $modelClass::factory()->create();

    expect($model->exists)->toBeTrue();
})->with([
    'organization' => Organization::class,
    'store' => Store::class,
    'store domain' => StoreDomain::class,
    'user' => User::class,
    'store user' => StoreUser::class,
    'store setting' => StoreSetting::class,
    'product' => Product::class,
    'product option' => ProductOption::class,
    'product option value' => ProductOptionValue::class,
    'product variant' => ProductVariant::class,
    'variant option value' => VariantOptionValue::class,
    'inventory item' => InventoryItem::class,
    'collection' => Collection::class,
    'collection product' => CollectionProduct::class,
    'product media' => ProductMedia::class,
    'theme' => Theme::class,
    'theme file' => ThemeFile::class,
    'theme setting' => ThemeSetting::class,
    'page' => Page::class,
    'navigation menu' => NavigationMenu::class,
    'navigation item' => NavigationItem::class,
    'search setting' => SearchSetting::class,
    'search query' => SearchQuery::class,
    'cart' => Cart::class,
    'cart line' => CartLine::class,
    'checkout' => Checkout::class,
    'shipping zone' => ShippingZone::class,
    'shipping rate' => ShippingRate::class,
    'tax setting' => TaxSetting::class,
    'discount' => Discount::class,
    'customer' => Customer::class,
    'customer address' => CustomerAddress::class,
    'order' => Order::class,
    'order line' => OrderLine::class,
    'payment' => Payment::class,
    'refund' => Refund::class,
    'fulfillment' => Fulfillment::class,
    'fulfillment line' => FulfillmentLine::class,
    'analytics event' => AnalyticsEvent::class,
    'analytics daily' => AnalyticsDaily::class,
    'app' => App::class,
    'app installation' => AppInstallation::class,
    'oauth client' => OauthClient::class,
    'oauth token' => OauthToken::class,
    'webhook subscription' => WebhookSubscription::class,
    'webhook delivery' => WebhookDelivery::class,
    'customer password reset token' => CustomerPasswordResetToken::class,
]);

it('casts enum attributes to enum instances', function () {
    expect(Store::factory()->create()->status)->toBeInstanceOf(StoreStatus::class)
        ->and(User::factory()->create()->status)->toBeInstanceOf(UserStatus::class)
        ->and(StoreUser::factory()->create()->role)->toBeInstanceOf(StoreUserRole::class)
        ->and(Product::factory()->create()->status)->toBeInstanceOf(ProductStatus::class)
        ->and(ProductMedia::factory()->create()->type)->toBeInstanceOf(MediaType::class)
        ->and(ProductMedia::factory()->create()->status)->toBeInstanceOf(MediaStatus::class)
        ->and(Checkout::factory()->create()->status)->toBeInstanceOf(CheckoutStatus::class)
        ->and(Checkout::factory()->create()->payment_method)->toBeInstanceOf(PaymentMethod::class)
        ->and(Discount::factory()->create()->type)->toBeInstanceOf(DiscountType::class)
        ->and(Discount::factory()->create()->value_type)->toBeInstanceOf(DiscountValueType::class)
        ->and(Order::factory()->create()->status)->toBeInstanceOf(OrderStatus::class)
        ->and(Order::factory()->create()->financial_status)->toBeInstanceOf(FinancialStatus::class)
        ->and(Order::factory()->create()->fulfillment_status)->toBeInstanceOf(FulfillmentStatus::class)
        ->and(Payment::factory()->create()->method)->toBeInstanceOf(PaymentMethod::class)
        ->and(Payment::factory()->create()->status)->toBeInstanceOf(PaymentStatus::class)
        ->and(Fulfillment::factory()->create()->status)->toBeInstanceOf(FulfillmentShipmentStatus::class);
});

it('casts json, boolean, hashed and encrypted attributes', function () {
    $product = Product::factory()->create();

    expect($product->tags)->toBeArray();

    $variant = ProductVariant::factory()->create();

    expect($variant->requires_shipping)->toBeBool()
        ->and($variant->is_default)->toBeBool();

    $customer = Customer::factory()->create();

    expect(Hash::check('password', $customer->password_hash))->toBeTrue();

    $payment = Payment::factory()->create();
    $stored = Illuminate\Support\Facades\DB::table('payments')->where('id', $payment->id)->value('raw_json_encrypted');

    expect($stored)->not->toContain('succeeded')
        ->and($payment->raw_json_encrypted)->toContain('succeeded');
});

it('resolves store membership roles', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    StoreUser::factory()->for($store, 'store')->for($user, 'user')->create(['role' => StoreUserRole::Owner]);

    expect($user->roleForStore($store))->toBe(StoreUserRole::Owner)
        ->and($user->stores)->toHaveCount(1)
        ->and($store->users)->toHaveCount(1);
});
