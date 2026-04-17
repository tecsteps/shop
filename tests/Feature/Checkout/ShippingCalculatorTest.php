<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->calculator = app(ShippingCalculator::class);
});

it('returns available rates for matching address', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'config_json' => ['amount' => 499],
    ]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Standard');
});

it('returns empty when no zone matches', function () {
    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'FR']);
    expect($rates)->toBeEmpty();
});

it('prefers more specific zone match', function () {
    $broadZone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $specificZone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Bavaria',
        'countries_json' => ['DE'],
        'regions_json' => ['DE-BY'],
    ]);

    ShippingRate::factory()->create(['zone_id' => $broadZone->id, 'name' => 'Standard']);
    ShippingRate::factory()->create(['zone_id' => $specificZone->id, 'name' => 'Bavaria Express']);

    $zone = $this->calculator->getMatchingZone($this->store, ['country' => 'DE', 'province_code' => 'DE-BY']);

    expect($zone->id)->toBe($specificZone->id);
});

it('calculates flat rate correctly', function () {
    $rate = new ShippingRate([
        'type' => 'flat',
        'config_json' => ['amount' => 499],
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $result = $this->calculator->calculate($rate, $cart);

    expect($result)->toBe(499);
});

it('calculates weight-based rate correctly', function () {
    $product = \App\Models\Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'weight_g' => 250,
        'requires_shipping' => true,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 3,
    ]);

    $rate = new ShippingRate([
        'type' => 'weight',
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
            ],
        ],
    ]);

    // 250g * 3 = 750g, falls in 501-2000 range
    $result = $this->calculator->calculate($rate, $cart);
    expect($result)->toBe(899);
});

it('returns null for weight exceeding all ranges', function () {
    $product = \App\Models\Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'weight_g' => 1000,
        'requires_shipping' => true,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    $rate = new ShippingRate([
        'type' => 'weight',
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 2000, 'amount' => 899],
            ],
        ],
    ]);

    // 1000g * 5 = 5000g, exceeds max 2000g
    $result = $this->calculator->calculate($rate, $cart);
    expect($result)->toBeNull();
});

it('calculates price-based rate correctly', function () {
    $product = \App\Models\Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 6000,
        'line_subtotal_amount' => 6000,
        'line_total_amount' => 6000,
    ]);

    $rate = new ShippingRate([
        'type' => 'price',
        'config_json' => [
            'ranges' => [
                ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
                ['min_amount' => 5001, 'amount' => 0],
            ],
        ],
    ]);

    $result = $this->calculator->calculate($rate, $cart);
    expect($result)->toBe(0);
});

it('excludes digital items from weight calculation', function () {
    $product = \App\Models\Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $physical = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'weight_g' => 300,
        'requires_shipping' => true,
    ]);
    $digital = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'weight_g' => 500,
        'requires_shipping' => false,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $physical->id, 'quantity' => 1]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $digital->id, 'quantity' => 1]);

    $weight = $this->calculator->getTotalShippingWeight($cart);
    expect($weight)->toBe(300);
});

it('breaks ties by lowest zone ID', function () {
    $zone1 = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Zone A',
        'countries_json' => ['US'],
    ]);

    $zone2 = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Zone B',
        'countries_json' => ['US'],
    ]);

    $matched = $this->calculator->getMatchingZone($this->store, ['country' => 'US']);
    expect($matched->id)->toBe($zone1->id);
});
