<?php

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = new ShippingCalculator;
});

it('matches zone by country', function () {
    $store = Store::factory()->create();
    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE', 'AT'],
    ]);

    $zone = $this->calculator->getMatchingZone($store, ['country' => 'DE']);

    expect($zone)->not->toBeNull()
        ->and($zone->countries_json)->toContain('DE');
});

it('returns null for unserviceable address', function () {
    $store = Store::factory()->create();
    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);

    $zone = $this->calculator->getMatchingZone($store, ['country' => 'JP']);

    expect($zone)->toBeNull();
});

it('prefers region match over country-only match', function () {
    $store = Store::factory()->create();

    $countryZone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $regionZone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'name' => 'Bavaria',
        'countries_json' => ['DE'],
        'regions_json' => ['DE-BY'],
    ]);

    $zone = $this->calculator->getMatchingZone($store, [
        'country' => 'DE',
        'province_code' => 'DE-BY',
    ]);

    expect($zone->id)->toBe($regionZone->id);
});

it('calculates flat rate', function () {
    $rate = ShippingRate::factory()->make([
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 500],
    ]);

    $cart = Cart::factory()->create();

    expect($this->calculator->calculate($rate, $cart))->toBe(500);
});

it('calculates weight-based rate', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $variant = ProductVariant::factory()->create([
        'weight_g' => 500,
        'requires_shipping' => true,
    ]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $rate = ShippingRate::factory()->weightBased()->make();

    $result = $this->calculator->calculate($rate, $cart);

    expect($result)->toBe(500); // 1000g is in range 0-1000
});

it('returns null for weight outside all ranges', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $variant = ProductVariant::factory()->create([
        'weight_g' => 10000,
        'requires_shipping' => true,
    ]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $rate = ShippingRate::factory()->weightBased()->make();

    expect($this->calculator->calculate($rate, $cart))->toBeNull();
});

it('calculates price-based rate', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'line_subtotal_amount' => 3000,
    ]);

    $rate = ShippingRate::factory()->priceBased()->make();

    expect($this->calculator->calculate($rate, $cart))->toBe(799);
});

it('returns free shipping for price above threshold', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'line_subtotal_amount' => 6000,
    ]);

    $rate = ShippingRate::factory()->priceBased()->make();

    expect($this->calculator->calculate($rate, $cart))->toBe(0);
});

it('gets available rates for a zone', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Standard']);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Express']);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'is_active' => false]);

    $rates = $this->calculator->getAvailableRates($store, ['country' => 'DE']);

    expect($rates)->toHaveCount(2);
});
