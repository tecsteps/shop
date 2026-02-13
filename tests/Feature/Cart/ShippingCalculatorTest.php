<?php

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->calculator = app(ShippingCalculator::class);
});

test('matches a zone by country code', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE', 'AT', 'CH'],
    ]);

    ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'DE']);

    expect($rates)->toHaveCount(1);
});

test('matches a zone by region code', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
        'regions_json' => ['US-NY', 'US-CA'],
    ]);

    ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $rates = $this->calculator->getAvailableRates($this->store, [
        'country' => 'US',
        'province_code' => 'US-NY',
    ]);

    expect($rates)->toHaveCount(1);
});

test('returns empty when no zone matches the address', function () {
    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'FR']);

    expect($rates)->toBeEmpty();
});

test('calculates a flat rate', function () {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    expect($this->calculator->calculate($rate, $cart))->toBe(499);
});

test('calculates a weight-based rate', function () {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->weightBased()->create(['zone_id' => $zone->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'weight_g' => 250,
        'requires_shipping' => true,
    ]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 3,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 3000,
        'line_total_amount' => 3000,
    ]);

    // Total weight: 250g * 3 = 750g, which is in range 0-1000g = 499
    expect($this->calculator->calculate($rate, $cart))->toBe(499);
});

test('calculates a price-based rate', function () {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->priceBased()->create(['zone_id' => $zone->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 7500,
        'line_subtotal_amount' => 7500,
        'line_total_amount' => 7500,
    ]);

    // Subtotal 7500 is in range 5001+ = free (0)
    expect($this->calculator->calculate($rate, $cart))->toBe(0);
});

test('returns zero shipping when no items require shipping', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->digital()->create(['product_id' => $product->id]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_total_amount' => 1000,
    ]);

    expect($this->calculator->cartRequiresShipping($cart))->toBeFalse();
});

test('skips inactive rates', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);

    ShippingRate::factory()->inactive()->create(['zone_id' => $zone->id]);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Active Rate']);

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'US']);

    expect($rates)->toHaveCount(1);
    expect($rates->first()->name)->toBe('Active Rate');
});

test('returns rates from multiple matching zones', function () {
    $zone1 = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
        'name' => 'US General',
    ]);
    $zone2 = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
        'name' => 'US Express',
    ]);

    ShippingRate::factory()->create(['zone_id' => $zone1->id, 'name' => 'Standard']);
    ShippingRate::factory()->create(['zone_id' => $zone2->id, 'name' => 'Express']);

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'US']);

    expect($rates)->toHaveCount(2);
});
