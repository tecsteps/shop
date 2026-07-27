<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\InventoryPolicy;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\TaxMode;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Models\User;
use App\Services\SearchService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
});

test('seeds the two stores with their domains', function () {
    expect(Store::query()->count())->toBe(2)
        ->and($this->fashion->name)->toBe('Acme Fashion')
        ->and($this->fashion->default_currency)->toBe('EUR')
        ->and($this->fashion->default_locale)->toBe('en')
        ->and($this->fashion->timezone)->toBe('Europe/Berlin')
        ->and($this->electronics->name)->toBe('Acme Electronics')
        ->and($this->electronics->default_currency)->toBe('EUR');

    $domains = StoreDomain::query()->orderBy('hostname')->get();

    expect($domains)->toHaveCount(3);

    $primary = $domains->firstWhere('hostname', 'acme-fashion.test');
    expect($primary->type)->toBe(\App\Enums\StoreDomainType::Storefront)
        ->and($primary->is_primary)->toBeTrue()
        ->and($primary->store_id)->toBe($this->fashion->id);

    $admin = $domains->firstWhere('hostname', 'admin.acme-fashion.test');
    expect($admin->type)->toBe(\App\Enums\StoreDomainType::Admin)
        ->and($admin->store_id)->toBe($this->fashion->id);

    $electronicsDomain = $domains->firstWhere('hostname', 'acme-electronics.test');
    expect($electronicsDomain->type)->toBe(\App\Enums\StoreDomainType::Storefront)
        ->and($electronicsDomain->is_primary)->toBeTrue()
        ->and($electronicsDomain->store_id)->toBe($this->electronics->id);
});

test('seeds the five admin users with their store roles', function () {
    expect(User::query()->count())->toBe(5);

    $roles = DB::table('store_users')
        ->join('users', 'users.id', '=', 'store_users.user_id')
        ->orderBy('users.email')
        ->pluck('store_users.role', 'users.email');

    expect($roles->all())->toBe([
        'admin2@acme.test' => 'owner',
        'admin@acme.test' => 'owner',
        'manager@acme.test' => 'admin',
        'staff@acme.test' => 'staff',
        'support@acme.test' => 'support',
    ]);

    $admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    expect(Hash::check('password', $admin->password_hash))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(DB::table('store_users')->where('user_id', $admin->id)->value('store_id'))->toBe($this->fashion->id);

    $adminTwo = User::query()->where('email', 'admin2@acme.test')->firstOrFail();

    expect(DB::table('store_users')->where('user_id', $adminTwo->id)->value('store_id'))->toBe($this->electronics->id);
});

test('admin can log in with the seeded credentials', function () {
    Livewire\Livewire::test(App\Livewire\Admin\Auth\Login::class)
        ->set('email', 'admin@acme.test')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/admin');

    $this->assertAuthenticated('web');
});

test('seeds store settings and tax settings', function () {
    $fashionSettings = StoreSettings::query()->find($this->fashion->id)->settings_json;
    $electronicsSettings = StoreSettings::query()->find($this->electronics->id)->settings_json;

    expect($fashionSettings)->toMatchArray([
        'store_name' => 'Acme Fashion',
        'contact_email' => 'hello@acme-fashion.test',
        'order_number_prefix' => '#',
        'order_number_start' => 1001,
    ])->and($electronicsSettings)->toMatchArray([
        'store_name' => 'Acme Electronics',
        'order_number_prefix' => '#',
        'order_number_start' => 5001,
    ]);

    foreach ([$this->fashion, $this->electronics] as $store) {
        $tax = TaxSettings::query()->find($store->id);

        expect($tax->mode)->toBe(TaxMode::Manual)
            ->and($tax->provider)->toBe('none')
            ->and($tax->prices_include_tax)->toBeTrue()
            ->and($tax->config_json)->toBe(['default_rate_bps' => 1900]);
    }
});

test('seeds shipping zones and rates', function () {
    $zones = ShippingZone::query()->where('store_id', $this->fashion->id)->with('rates')->get()->keyBy('name');

    expect($zones)->toHaveCount(3)
        ->and($zones['Domestic']->countries_json)->toBe(['DE'])
        ->and($zones['Domestic']->rates->pluck('config_json.amount', 'name')->all())
        ->toBe(['Standard Shipping' => 499, 'Express Shipping' => 999])
        ->and($zones['EU']->countries_json)->toBe(['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'])
        ->and($zones['EU']->rates->sole()->config_json)->toBe(['amount' => 899])
        ->and($zones['Rest of World']->countries_json)->toBe(['US', 'GB', 'CA', 'AU'])
        ->and($zones['Rest of World']->rates->sole()->config_json)->toBe(['amount' => 1499]);

    $electronicsZone = ShippingZone::query()->where('store_id', $this->electronics->id)->with('rates')->sole();

    expect($electronicsZone->name)->toBe('Germany')
        ->and($electronicsZone->rates->sole()->config_json)->toBe(['amount' => 0]);
});

test('seeds the fashion catalog with exact products, variants and inventory', function () {
    $products = Product::query()->where('store_id', $this->fashion->id)->get()->keyBy('handle');

    expect($products)->toHaveCount(20)
        ->and(Collection::query()->where('store_id', $this->fashion->id)->orderBy('id')->pluck('handle')->all())
        ->toBe(['new-arrivals', 't-shirts', 'pants-jeans', 'sale']);

    // Product #1: Classic Cotton T-Shirt, 12 variants (4 sizes x 3 colors).
    $tshirt = $products['classic-cotton-t-shirt']->load(['variants.optionValues', 'options.values']);

    expect($tshirt->status)->toBe(ProductStatus::Active)
        ->and($tshirt->variants)->toHaveCount(12)
        ->and($tshirt->variants->pluck('price_amount')->unique()->all())->toBe([2499])
        ->and($tshirt->options->pluck('name')->all())->toBe(['Size', 'Color'])
        ->and($tshirt->options->firstWhere('name', 'Size')->values->pluck('value')->all())->toBe(['S', 'M', 'L', 'XL'])
        ->and($tshirt->options->firstWhere('name', 'Color')->values->pluck('value')->all())->toBe(['White', 'Black', 'Navy']);

    $defaultVariant = $tshirt->variants->firstWhere('is_default', true);

    expect($defaultVariant->sku)->toBe('ACME-CTSH-S-WHT')
        ->and($defaultVariant->position)->toBe(0)
        ->and($defaultVariant->optionValues->pluck('value')->sort()->values()->all())->toBe(['S', 'White']);

    foreach ($tshirt->variants as $variant) {
        expect($variant->sku)->toStartWith('ACME-CTSH-')
            ->and($variant->inventoryItem->quantity_on_hand)->toBe(15)
            ->and($variant->inventoryItem->policy)->toBe(InventoryPolicy::Deny);
    }

    // Product #15 draft, #16 archived: hidden from the storefront.
    expect($products['unreleased-winter-jacket']->status)->toBe(ProductStatus::Draft)
        ->and($products['unreleased-winter-jacket']->published_at)->toBeNull()
        ->and($products['discontinued-raincoat']->status)->toBe(ProductStatus::Archived);

    // Product #17: sold out with deny policy. Product #18: backorderable.
    $soldOut = $products['limited-edition-sneakers'];
    $backorder = $products['backorder-denim-jacket'];

    expect($soldOut->status)->toBe(ProductStatus::Active)
        ->and($soldOut->variants->every(
            fn ($variant) => $variant->inventoryItem->quantity_on_hand === 0
                && $variant->inventoryItem->policy === InventoryPolicy::Deny
        ))->toBeTrue()
        ->and($backorder->variants->every(
            fn ($variant) => $variant->inventoryItem->quantity_on_hand === 0
                && $variant->inventoryItem->policy === InventoryPolicy::Continue
        ))->toBeTrue();

    // Product #19: digital gift card with three denominations.
    $giftCard = $products['gift-card']->load('variants');

    expect($giftCard->variants->pluck('price_amount', 'sku')->all())->toBe([
        'ACME-GIFT-25' => 2500,
        'ACME-GIFT-50' => 5000,
        'ACME-GIFT-100' => 10000,
    ])->and($giftCard->variants->pluck('requires_shipping')->unique()->all())->toBe([false]);

    // Total variant count and sale pricing.
    expect(DB::table('product_variants')->whereIn('product_id', $products->pluck('id'))->count())->toBe(117)
        ->and($products['premium-slim-fit-jeans']->variants->pluck('compare_at_amount')->unique()->all())->toBe([9999])
        ->and($products['cashmere-overcoat']->variants->pluck('price_amount')->unique()->all())->toBe([49999]);
});

test('seeds the fashion collection memberships with positions', function () {
    $memberships = function (string $handle): array {
        return Collection::query()
            ->where('store_id', $this->fashion->id)
            ->where('handle', $handle)
            ->firstOrFail()
            ->products()
            ->get()
            ->pluck('handle')
            ->all();
    };

    expect($memberships('new-arrivals'))->toBe([
        'classic-cotton-t-shirt', 'premium-slim-fit-jeans', 'organic-hoodie',
        'running-sneakers', 'chino-shorts', 'bucket-hat', 'cashmere-overcoat',
    ])->and($memberships('t-shirts'))->toBe([
        'classic-cotton-t-shirt', 'graphic-print-tee', 'v-neck-linen-tee', 'striped-polo-shirt',
    ])->and($memberships('pants-jeans'))->toBe([
        'premium-slim-fit-jeans', 'cargo-pants', 'chino-shorts', 'wide-leg-trousers',
    ])->and($memberships('sale'))->toBe([
        'premium-slim-fit-jeans', 'striped-polo-shirt', 'wide-leg-trousers',
    ]);
});

test('seeds the electronics catalog for tenant isolation', function () {
    $products = Product::query()->where('store_id', $this->electronics->id)->get()->keyBy('handle');

    expect($products)->toHaveCount(5)
        ->and($products->keys()->sort()->values()->all())->toBe([
            'mechanical-keyboard', 'monitor-stand', 'pro-laptop-15', 'usb-c-cable-2m', 'wireless-headphones',
        ])
        ->and($products['pro-laptop-15']->variants()->orderBy('position')->pluck('price_amount')->all())
        ->toBe([99999, 119999, 149999])
        ->and($products['usb-c-cable-2m']->variants)->toHaveCount(1)
        ->and($products['usb-c-cable-2m']->variants->sole()->price_amount)->toBe(1299)
        ->and($products['monitor-stand']->variants)->toHaveCount(1);

    expect(Collection::query()->where('store_id', $this->electronics->id)->orderBy('id')->pluck('handle')->all())
        ->toBe(['featured', 'accessories']);
});

test('seeds the five discount codes', function () {
    $discounts = Discount::query()->where('store_id', $this->fashion->id)->get()->keyBy('code');

    expect($discounts)->toHaveCount(5);

    $welcome = $discounts['WELCOME10'];
    expect($welcome->value_type)->toBe(DiscountValueType::Percent)
        ->and($welcome->value_amount)->toBe(10)
        ->and($welcome->rules_json)->toBe(['min_purchase_amount' => 2000])
        ->and($welcome->usage_count)->toBe(3)
        ->and($welcome->status)->toBe(DiscountStatus::Active);

    expect($discounts['FLAT5']->value_type)->toBe(DiscountValueType::Fixed)
        ->and($discounts['FLAT5']->value_amount)->toBe(500)
        ->and($discounts['FREESHIP']->value_type)->toBe(DiscountValueType::FreeShipping)
        ->and($discounts['EXPIRED20']->status)->toBe(DiscountStatus::Expired)
        ->and($discounts['EXPIRED20']->ends_at->isPast())->toBeTrue();

    $maxed = $discounts['MAXED'];
    expect($maxed->usage_limit)->toBe(5)
        ->and($maxed->usage_count)->toBeGreaterThanOrEqual($maxed->usage_limit);
});

test('seeds customers with addresses', function () {
    expect(Customer::query()->where('store_id', $this->fashion->id)->count())->toBe(10)
        ->and(Customer::query()->where('store_id', $this->electronics->id)->count())->toBe(2);

    $john = Customer::query()->where('store_id', $this->fashion->id)->where('email', 'customer@acme.test')->firstOrFail();

    expect($john->name)->toBe('John Doe')
        ->and($john->marketing_opt_in)->toBeTrue()
        ->and(Hash::check('password', $john->password_hash))->toBeTrue()
        ->and($john->addresses)->toHaveCount(2)
        ->and($john->addresses->firstWhere('is_default', true)->label)->toBe('Home')
        ->and($john->addresses->firstWhere('label', 'Home')->address_json)
        ->toMatchArray(['address1' => 'Hauptstrasse 1', 'city' => 'Berlin', 'country_code' => 'DE']);
});

test('seeds orders with lines, payments, fulfillments and refunds', function () {
    $orders = Order::query()->where('store_id', $this->fashion->id)->get()->keyBy('order_number');

    expect($orders)->toHaveCount(15);

    $john = Customer::query()->where('store_id', $this->fashion->id)->where('email', 'customer@acme.test')->firstOrFail();
    $johnsNumbers = $orders->where('customer_id', $john->id)->keys()->sort()->values()->all();

    expect($johnsNumbers)->toBe(['#1001', '#1002', '#1004', '#1010', '#1015']);

    // #1001: paid, awaiting fulfillment.
    $order1001 = $orders['#1001'];
    expect($order1001->status)->toBe(OrderStatus::Paid)
        ->and($order1001->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order1001->fulfillment_status)->toBe(FulfillmentOrderStatus::Unfulfilled)
        ->and($order1001->total_amount)->toBe(5497)
        ->and($order1001->subtotal_amount)->toBe(4998)
        ->and($order1001->shipping_amount)->toBe(499)
        ->and($order1001->tax_amount)->toBe(798)
        ->and($order1001->lines)->toHaveCount(1)
        ->and($order1001->lines->sole()->title_snapshot)->toBe('Classic Cotton T-Shirt')
        ->and($order1001->lines->sole()->sku_snapshot)->toBe('ACME-CTSH-S-WHT')
        ->and($order1001->lines->sole()->quantity)->toBe(2)
        ->and($order1001->payments->sole()->status)->toBe(PaymentStatus::Captured)
        ->and($order1001->payments->sole()->provider_payment_id)->toBe('mock_test_order1001')
        ->and($order1001->fulfillments)->toHaveCount(0);

    // #1002: delivered with a full fulfillment.
    expect($orders['#1002']->fulfillment_status)->toBe(FulfillmentOrderStatus::Fulfilled)
        ->and($orders['#1002']->fulfillments->sole()->tracking_number)->toBe('DHL1234567890')
        ->and($orders['#1002']->fulfillments->sole()->lines)->toHaveCount(2);

    // #1003: partially fulfilled - only the jeans line shipped.
    expect($orders['#1003']->fulfillment_status)->toBe(FulfillmentOrderStatus::Partial)
        ->and($orders['#1003']->fulfillments->sole()->lines)->toHaveCount(1);

    // #1004: cancelled with a full refund.
    $order1004 = $orders['#1004'];
    expect($order1004->status)->toBe(OrderStatus::Cancelled)
        ->and($order1004->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order1004->payments->sole()->status)->toBe(PaymentStatus::Refunded)
        ->and($order1004->refunds->sole()->amount)->toBe(2998)
        ->and($order1004->refunds->sole()->provider_refund_id)->toBe('mock_re_test_order1004');

    // #1005: bank transfer awaiting payment.
    $order1005 = $orders['#1005'];
    expect($order1005->payment_method)->toBe(PaymentMethod::BankTransfer)
        ->and($order1005->status)->toBe(OrderStatus::Pending)
        ->and($order1005->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order1005->payments->sole()->status)->toBe(PaymentStatus::Pending)
        ->and($order1005->total_amount)->toBe(3998);

    // #1008: partial refund.
    expect($orders['#1008']->financial_status)->toBe(FinancialStatus::PartiallyRefunded)
        ->and($orders['#1008']->refunds->sole()->amount)->toBe(2999);

    // #1015: WELCOME10 discount allocated on both lines.
    $order1015 = $orders['#1015'];
    $welcomeId = Discount::query()->where('store_id', $this->fashion->id)->where('code', 'WELCOME10')->value('id');
    $allocations = $order1015->lines->pluck('discount_allocations_json');

    expect($order1015->discount_amount)->toBe(550)
        ->and($order1015->total_amount)->toBe(5447)
        ->and($allocations[0])->toBe([['discount_id' => $welcomeId, 'amount' => 250]])
        ->and($allocations[1])->toBe([['discount_id' => $welcomeId, 'amount' => 300]]);

    // Electronics orders start at #5001.
    $electronicsOrders = Order::query()->where('store_id', $this->electronics->id)->orderBy('order_number')->get();

    expect($electronicsOrders)->toHaveCount(3)
        ->and($electronicsOrders->pluck('order_number')->all())->toBe(['#5001', '#5002', '#5003'])
        ->and($electronicsOrders->firstWhere('order_number', '#5001')->total_amount)->toBe(121298)
        ->and($electronicsOrders->firstWhere('order_number', '#5003')->status)->toBe(OrderStatus::Pending);
});

test('seeds themes, pages and navigation', function () {
    foreach ([$this->fashion, $this->electronics] as $store) {
        $theme = \App\Models\Theme::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', \App\Enums\ThemeStatus::Published)
            ->first();

        expect($theme)->not->toBeNull()
            ->and($theme->name)->toBe('Default Theme');
    }

    $fashionThemeSettings = DB::table('theme_settings')
        ->join('themes', 'themes.id', '=', 'theme_settings.theme_id')
        ->where('themes.store_id', $this->fashion->id)
        ->value('theme_settings.settings_json');

    $fashionThemeSettings = json_decode($fashionThemeSettings, true);

    expect($fashionThemeSettings['hero']['heading'])->toBe('Welcome to Acme Fashion')
        ->and($fashionThemeSettings['announcement']['enabled'])->toBeTrue()
        ->and($fashionThemeSettings['featured_collections']['collection_handles'])->toBe(['new-arrivals', 't-shirts', 'sale']);

    $about = Page::query()->where('store_id', $this->fashion->id)->where('handle', 'about')->firstOrFail();

    expect($about->status)->toBe(\App\Enums\PageStatus::Published)
        ->and($about->published_at)->not->toBeNull()
        ->and(Page::query()->where('store_id', $this->fashion->id)->count())->toBe(5);

    $mainMenuItems = DB::table('navigation_items')
        ->join('navigation_menus', 'navigation_menus.id', '=', 'navigation_items.menu_id')
        ->where('navigation_menus.store_id', $this->fashion->id)
        ->where('navigation_menus.handle', 'main-menu')
        ->orderBy('navigation_items.position')
        ->get(['navigation_items.label', 'navigation_items.type', 'navigation_items.url']);

    expect($mainMenuItems->pluck('label')->all())->toBe(['Home', 'New Arrivals', 'T-Shirts', 'Pants & Jeans', 'Sale'])
        ->and($mainMenuItems->first()->type)->toBe('link')
        ->and($mainMenuItems->first()->url)->toBe('/');

    $footerMenuCount = DB::table('navigation_items')
        ->join('navigation_menus', 'navigation_menus.id', '=', 'navigation_items.menu_id')
        ->where('navigation_menus.store_id', $this->fashion->id)
        ->where('navigation_menus.handle', 'footer-menu')
        ->count();

    expect($footerMenuCount)->toBe(5);
});

test('seeds analytics daily rows and events', function () {
    expect(DB::table('analytics_daily')->where('store_id', $this->fashion->id)->count())->toBe(31)
        ->and(DB::table('analytics_events')->where('store_id', $this->fashion->id)->count())->toBe(220)
        ->and(DB::table('analytics_daily')->where('store_id', $this->fashion->id)->where('date', now()->toDateString())->exists())->toBeTrue();
});

test('indexes the catalog in the full-text search table', function () {
    expect(DB::table('products_fts')->count())->toBe(25);

    $results = app(SearchService::class)->search($this->fashion, 'cotton');

    expect($results->total())->toBeGreaterThanOrEqual(1)
        ->and($results->getCollection()->pluck('handle')->all())->toContain('classic-cotton-t-shirt');
});

test('is idempotent when run twice', function () {
    $counts = fn (): array => [
        'organizations' => DB::table('organizations')->count(),
        'stores' => DB::table('stores')->count(),
        'store_domains' => DB::table('store_domains')->count(),
        'users' => DB::table('users')->count(),
        'store_users' => DB::table('store_users')->count(),
        'products' => DB::table('products')->count(),
        'product_variants' => DB::table('product_variants')->count(),
        'product_options' => DB::table('product_options')->count(),
        'product_option_values' => DB::table('product_option_values')->count(),
        'variant_option_values' => DB::table('variant_option_values')->count(),
        'inventory_items' => DB::table('inventory_items')->count(),
        'collections' => DB::table('collections')->count(),
        'collection_products' => DB::table('collection_products')->count(),
        'discounts' => DB::table('discounts')->count(),
        'customers' => DB::table('customers')->count(),
        'customer_addresses' => DB::table('customer_addresses')->count(),
        'orders' => DB::table('orders')->count(),
        'order_lines' => DB::table('order_lines')->count(),
        'payments' => DB::table('payments')->count(),
        'refunds' => DB::table('refunds')->count(),
        'fulfillments' => DB::table('fulfillments')->count(),
        'fulfillment_lines' => DB::table('fulfillment_lines')->count(),
        'themes' => DB::table('themes')->count(),
        'pages' => DB::table('pages')->count(),
        'navigation_menus' => DB::table('navigation_menus')->count(),
        'navigation_items' => DB::table('navigation_items')->count(),
        'analytics_daily' => DB::table('analytics_daily')->count(),
        'analytics_events' => DB::table('analytics_events')->count(),
    ];

    $before = $counts();

    $this->seed(DatabaseSeeder::class);

    expect($counts())->toBe($before);
});
