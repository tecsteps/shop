<?php

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;

it('matches a zone by country code', function () {
    $ctx = createStoreContext();
    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'DACH',
        'countries_json' => ['DE', 'AT', 'CH'],
        'regions_json' => [],
    ]);
    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $calculator = new ShippingCalculator;
    $rates = $calculator->getAvailableRates($ctx['store'], ['country' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Standard');
});

it('matches a zone by region code', function () {
    $ctx = createStoreContext();
    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'US States',
        'countries_json' => ['US'],
        'regions_json' => ['US-NY', 'US-CA'],
    ]);
    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'US Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 999],
        'is_active' => true,
    ]);

    $calculator = new ShippingCalculator;
    $rates = $calculator->getAvailableRates($ctx['store'], ['country' => 'US', 'province_code' => 'US-NY']);

    expect($rates)->toHaveCount(1);
});

it('returns empty when no zone matches the address', function () {
    $ctx = createStoreContext();
    ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'DE Only',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $calculator = new ShippingCalculator;
    $rates = $calculator->getAvailableRates($ctx['store'], ['country' => 'FR']);

    expect($rates)->toBeEmpty();
});

it('calculates a flat rate', function () {
    $ctx = createStoreContext();
    $rate = new ShippingRate([
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    $calculator = new ShippingCalculator;
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(499);
});

it('calculates a weight-based rate', function () {
    $ctx = createStoreContext();

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Test',
        'handle' => 'test-weight',
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
        'weight_g' => 250,
        'requires_shipping' => true,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 3, // 750g total
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 7500,
        'line_discount_amount' => 0,
        'line_total_amount' => 7500,
    ]);

    $rate = new ShippingRate([
        'type' => ShippingRateType::Weight,
        'config_json' => ['ranges' => [
            ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
            ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
        ]],
    ]);

    $calculator = new ShippingCalculator;
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(899);
});

it('calculates a price-based rate', function () {
    $ctx = createStoreContext();
    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Test',
        'handle' => 'test-price-rate',
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 7500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 7500,
        'line_subtotal_amount' => 7500,
        'line_discount_amount' => 0,
        'line_total_amount' => 7500,
    ]);

    $rate = new ShippingRate([
        'type' => ShippingRateType::Price,
        'config_json' => ['ranges' => [
            ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
            ['min_amount' => 5001, 'max_amount' => 999999, 'amount' => 399],
        ]],
    ]);

    $calculator = new ShippingCalculator;
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(399);
});

it('returns zero shipping when no items require shipping', function () {
    $ctx = createStoreContext();
    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Digital',
        'handle' => 'digital-product',
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
        'requires_shipping' => false,
        'weight_g' => 0,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_discount_amount' => 0,
        'line_total_amount' => 1000,
    ]);

    $rate = new ShippingRate([
        'type' => ShippingRateType::Weight,
        'config_json' => ['ranges' => [['min_g' => 0, 'max_g' => 5000, 'amount' => 899]]],
    ]);

    $calculator = new ShippingCalculator;
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(0);
});

it('returns the correct rate when multiple zones match', function () {
    $ctx = createStoreContext();

    $zone1 = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'US General',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);
    ShippingRate::create([
        'zone_id' => $zone1->id,
        'name' => 'US Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 999],
        'is_active' => true,
    ]);

    $zone2 = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'US NY',
        'countries_json' => ['US'],
        'regions_json' => ['US-NY'],
    ]);
    ShippingRate::create([
        'zone_id' => $zone2->id,
        'name' => 'NY Express',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $calculator = new ShippingCalculator;
    $rates = $calculator->getAvailableRates($ctx['store'], ['country' => 'US', 'province_code' => 'US-NY']);

    // Should get rates from both zones (region match first, then country match)
    expect($rates)->toHaveCount(2);
});

it('skips inactive rates', function () {
    $ctx = createStoreContext();
    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'DE',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Active',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Inactive',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 999],
        'is_active' => false,
    ]);

    $calculator = new ShippingCalculator;
    $rates = $calculator->getAvailableRates($ctx['store'], ['country' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Active');
});
