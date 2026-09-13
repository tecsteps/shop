<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\InventoryItem;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\SearchSetting;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSetting;
use App\Models\StoreUser;
use App\Models\TaxSetting;
use App\Models\Theme;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('seeds the organization, stores, domains, users, and roles', function () {
    expect(Organization::count())->toBe(1)
        ->and(Store::count())->toBe(2)
        ->and(StoreDomain::count())->toBe(3)
        ->and(User::count())->toBe(5)
        ->and(StoreUser::count())->toBe(5);

    $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
    $admin = User::where('email', 'admin@acme.test')->firstOrFail();

    expect($admin->roleForStore($fashion)->value)->toBe('owner');
    expect(StoreDomain::where('hostname', 'admin.acme-fashion.test')->firstOrFail()->type->value)->toBe('admin');
});

it('seeds store settings, tax settings, and shipping zones', function () {
    expect(StoreSetting::count())->toBe(2)
        ->and(TaxSetting::count())->toBe(2)
        ->and(ShippingZone::count())->toBe(4)
        ->and(ShippingRate::count())->toBe(5);

    $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();

    expect($fashion->taxSetting->config_json['default_rate_bps'])->toBe(1900)
        ->and($fashion->storeSetting->settings_json['order_number_start'])->toBe(1001);
});

it('seeds the exact product catalog with variants and inventory', function () {
    $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
    $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

    expect(Product::count())->toBe(25)
        ->and(Product::where('store_id', $fashion->id)->count())->toBe(20)
        ->and(Product::where('store_id', $electronics->id)->count())->toBe(5)
        ->and(ProductVariant::count())->toBe(127)
        ->and(InventoryItem::count())->toBe(127);

    $tee = Product::where('store_id', $fashion->id)->where('handle', 'classic-cotton-t-shirt')->firstOrFail();

    expect($tee->productVariants()->count())->toBe(12)
        ->and($tee->productVariants()->where('is_default', true)->count())->toBe(1);

    expect(Product::where('handle', 'unreleased-winter-jacket')->firstOrFail()->status)->toBe(ProductStatus::Draft)
        ->and(Product::where('handle', 'discontinued-raincoat')->firstOrFail()->status)->toBe(ProductStatus::Archived);

    $soldOut = Product::where('handle', 'limited-edition-sneakers')->firstOrFail();

    expect($soldOut->productVariants()->first()->inventoryItem->quantity_on_hand)->toBe(0)
        ->and($soldOut->productVariants()->first()->inventoryItem->policy->value)->toBe('deny');

    $backorder = Product::where('handle', 'backorder-denim-jacket')->firstOrFail();

    expect($backorder->productVariants()->first()->inventoryItem->policy->value)->toBe('continue');

    $gift = Product::where('handle', 'gift-card')->firstOrFail();

    expect($gift->productVariants()->where('requires_shipping', false)->count())->toBe(3);
});

it('seeds collections with ordered product assignments', function () {
    expect(Collection::count())->toBe(6)
        ->and(CollectionProduct::count())->toBe(23);

    $newArrivals = Collection::where('handle', 'new-arrivals')->firstOrFail();
    $handles = $newArrivals->products()->orderByPivot('position')->pluck('handle')->all();

    expect($handles)->toBe([
        'classic-cotton-t-shirt',
        'premium-slim-fit-jeans',
        'organic-hoodie',
        'running-sneakers',
        'chino-shorts',
        'bucket-hat',
        'cashmere-overcoat',
    ]);
});

it('seeds discounts with behavioral states', function () {
    expect(Discount::count())->toBe(5);

    $welcome = Discount::where('code', 'WELCOME10')->firstOrFail();

    expect($welcome->value_amount)->toBe(10)
        ->and($welcome->rules_json['min_purchase_amount'])->toBe(2000);

    expect(Discount::where('code', 'EXPIRED20')->firstOrFail()->status->value)->toBe('expired');

    $maxed = Discount::where('code', 'MAXED')->firstOrFail();

    expect($maxed->usage_limit)->toBe(5)
        ->and($maxed->usage_count)->toBe(5);
});

it('seeds customers with addresses', function () {
    expect(Customer::count())->toBe(12)
        ->and(CustomerAddress::count())->toBe(13);

    $customer = Customer::where('email', 'customer@acme.test')->firstOrFail();

    expect($customer->customerAddresses()->count())->toBe(2)
        ->and($customer->customerAddresses()->where('is_default', true)->count())->toBe(1);
});

it('seeds orders with lines, payments, fulfillments, and refunds', function () {
    expect(Order::count())->toBe(18)
        ->and(OrderLine::count())->toBe(26)
        ->and(Payment::count())->toBe(18)
        ->and(Fulfillment::count())->toBe(7)
        ->and(FulfillmentLine::count())->toBe(11)
        ->and(Refund::count())->toBe(2);

    $pending = Order::where('order_number', '#1005')->firstOrFail();

    expect($pending->status)->toBe(OrderStatus::Pending)
        ->and($pending->payment_method)->toBe(PaymentMethod::BankTransfer);

    $digital = Order::where('order_number', '#1014')->firstOrFail();

    expect($digital->shipping_amount)->toBe(0);

    $discounted = Order::where('order_number', '#1015')->firstOrFail();

    expect($discounted->discount_amount)->toBe(550)
        ->and($discounted->orderLines()->first()->discount_allocations_json[0]['amount'])->toBe(250);
});

it('seeds themes, pages, and navigation', function () {
    expect(Theme::count())->toBe(2)
        ->and(Page::count())->toBe(5)
        ->and(NavigationMenu::count())->toBe(3)
        ->and(NavigationItem::count())->toBe(13);

    $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
    $theme = Theme::where('store_id', $fashion->id)->firstOrFail();

    expect($theme->themeSetting->settings_json['hero_heading'])->toBe('Welcome to Acme Fashion');

    $mainMenu = NavigationMenu::where('store_id', $fashion->id)->where('handle', 'main-menu')->firstOrFail();

    expect($mainMenu->navigationItems()->orderBy('position')->pluck('label')->all())->toBe([
        'Home', 'New Arrivals', 'T-Shirts', 'Pants & Jeans', 'Sale',
    ]);
});

it('seeds analytics aggregates, events, and search settings', function () {
    expect(AnalyticsDaily::count())->toBe(31)
        ->and(AnalyticsEvent::count())->toBe(220)
        ->and(SearchSetting::count())->toBe(2);

    $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();

    expect($fashion->searchSetting->synonyms_json)->toBe([
        ['tee', 't-shirt', 'tshirt'],
        ['pants', 'trousers', 'jeans'],
        ['sneakers', 'trainers', 'shoes'],
        ['hoodie', 'sweatshirt'],
    ]);
});

it('is idempotent when run twice', function () {
    $this->seed();

    expect(Organization::count())->toBe(1)
        ->and(Store::count())->toBe(2)
        ->and(Product::count())->toBe(25)
        ->and(ProductVariant::count())->toBe(127)
        ->and(Order::count())->toBe(18)
        ->and(AnalyticsDaily::count())->toBe(31);
});
