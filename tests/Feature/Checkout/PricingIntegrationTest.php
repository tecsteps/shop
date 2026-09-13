<?php

use App\Enums\CheckoutStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\TaxMode;
use App\Enums\TaxProvider;
use App\Models\Discount;
use App\Models\TaxSetting;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function pricingContext(): array
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 2]]);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 499);

    TaxSetting::factory()->create([
        'store_id' => $store->id,
        'mode' => TaxMode::Manual,
        'provider' => TaxProvider::None,
        'prices_include_tax' => false,
        'config_json' => ['default_tax_rate' => 1900],
    ]);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);

    return ['store' => $store, 'cart' => $cart, 'rate' => $rate, 'checkout' => $checkout];
}

it('calculates correct totals for a simple checkout', function () {
    $totals = pricingContext()['checkout']->refresh()->totals_json;

    expect($totals['subtotal'])->toBe(5000);
    expect($totals['discount'])->toBe(0);
    expect($totals['shipping'])->toBe(499);
    expect($totals['tax'])->toBe(1044);
    expect($totals['total'])->toBe(6543);
    expect($totals['currency'])->toBe('USD');
});

it('applies discount code and recalculates', function () {
    $ctx = pricingContext();

    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'usage_limit' => null,
        'usage_count' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active,
    ]);

    $checkout = app(CheckoutService::class)->applyDiscount($ctx['checkout'], 'SAVE10', app(DiscountService::class));
    $totals = $checkout->totals_json;

    expect($totals['discount'])->toBe(500);
    expect($totals['tax'])->toBe(949);
    expect($totals['total'])->toBe(5948);
});

it('stores pricing snapshot in totals json', function () {
    $totals = pricingContext()['checkout']->refresh()->totals_json;

    expect($totals)->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax', 'total', 'currency', 'tax_lines']);
});

it('recalculates on shipping method change', function () {
    $ctx = pricingContext();

    $express = App\Models\ShippingRate::factory()->create([
        'zone_id' => $ctx['rate']->zone_id,
        'type' => App\Enums\ShippingRateType::Flat,
        'config_json' => ['amount' => 899],
        'is_active' => true,
    ]);
    $checkout = app(CheckoutService::class)->setShippingMethod($ctx['checkout'], $express->id);
    $totals = $checkout->totals_json;

    expect($totals['shipping'])->toBe(899);
    expect($totals['tax'])->toBe(1120);
    expect($totals['total'])->toBe(7019);
});

it('handles prices-include-tax correctly', function () {
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 2]]);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 499);

    TaxSetting::factory()->create([
        'store_id' => $store->id,
        'mode' => TaxMode::Manual,
        'provider' => TaxProvider::None,
        'prices_include_tax' => true,
        'config_json' => ['default_tax_rate' => 1900],
    ]);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);

    $totals = $checkout->refresh()->totals_json;

    expect($totals['tax'])->toBe(879);
    expect($totals['total'])->toBe(5499);
    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);
});
