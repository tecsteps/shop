<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\Pricing\PricingService;

beforeEach(function () {
    $this->store = bindCurrentStore(makeStore());
    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => 'manual',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 0.19],
    ]);
});

function makeCartWithLine(int $storeId, int $unitPrice = 1000, int $quantity = 2): Cart
{
    $product = Product::create([
        'store_id' => $storeId,
        'title' => 'Test Product',
        'handle' => 'test-'.uniqid(),
        'status' => 'active',
        'description_html' => '',
        'tags' => [],
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'SKU-'.uniqid(),
        'price_amount' => $unitPrice,
        'currency' => 'EUR',
        'requires_shipping' => true,
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);

    $cart = Cart::create([
        'store_id' => $storeId,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price_amount' => $unitPrice,
        'line_subtotal_amount' => $unitPrice * $quantity,
        'line_total_amount' => $unitPrice * $quantity,
    ]);

    return $cart->fresh(['lines']);
}

it('computes subtotal from cart lines', function () {
    $cart = makeCartWithLine($this->store->id, 1500, 3);

    $totals = app(PricingService::class)->computeTotals($cart);

    expect($totals->subtotal)->toBe(4500)
        ->and($totals->total)->toBeGreaterThanOrEqual(4500);
});

it('applies percent discount', function () {
    $cart = makeCartWithLine($this->store->id, 10000, 1);

    $discount = Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code->value,
        'code' => 'TEN',
        'value_type' => DiscountValueType::Percent->value,
        'value_amount' => 10,
        'rules_json' => [],
        'status' => DiscountStatus::Active->value,
    ]);

    $totals = app(PricingService::class)->computeTotals($cart, null, $discount);

    expect($totals->discount)->toBe(1000);
});

it('applies fixed discount capped at subtotal', function () {
    $cart = makeCartWithLine($this->store->id, 500, 1);

    $discount = Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code->value,
        'code' => 'BIG',
        'value_type' => DiscountValueType::Fixed->value,
        'value_amount' => 2000,
        'rules_json' => [],
        'status' => DiscountStatus::Active->value,
    ]);

    $totals = app(PricingService::class)->computeTotals($cart, null, $discount);

    expect($totals->discount)->toBe(500);
});

it('waives shipping with free shipping discount', function () {
    $cart = makeCartWithLine($this->store->id, 1000, 1);

    $zone = ShippingZone::create([
        'store_id' => $this->store->id,
        'name' => 'Zone',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Flat',
        'type' => 'flat',
        'config_json' => ['amount' => 999],
        'is_active' => true,
    ]);

    $discount = Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code->value,
        'code' => 'SHIPFREE',
        'value_type' => DiscountValueType::FreeShipping->value,
        'value_amount' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active->value,
    ]);

    $totals = app(PricingService::class)->computeTotals($cart, $rate, $discount);

    expect($totals->shipping)->toBe(0);
});

it('computes tax on discounted subtotal', function () {
    $cart = makeCartWithLine($this->store->id, 10000, 1);

    $totals = app(PricingService::class)->computeTotals($cart);

    expect($totals->tax)->toBe(1900);
});
