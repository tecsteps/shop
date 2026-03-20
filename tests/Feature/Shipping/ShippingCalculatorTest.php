<?php

use App\Enums\ShippingRateType;
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

it('matches a zone by country', function () {
    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'US Zone',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);

    $zone = $this->calculator->getMatchingZone($this->store, ['country' => 'US']);

    expect($zone)->not->toBeNull()
        ->and($zone->name)->toBe('US Zone');
});

it('returns null when no zone matches', function () {
    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);

    $zone = $this->calculator->getMatchingZone($this->store, ['country' => 'JP']);

    expect($zone)->toBeNull();
});

it('prefers a more specific zone match', function () {
    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'US General',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);

    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'US-CA Specific',
        'countries_json' => ['US'],
        'regions_json' => ['US-CA'],
    ]);

    $zone = $this->calculator->getMatchingZone($this->store, [
        'country' => 'US',
        'province_code' => 'US-CA',
    ]);

    expect($zone->name)->toBe('US-CA Specific');
});

it('returns available rates for a matching zone', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);

    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Standard', 'is_active' => true]);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Express', 'is_active' => true]);
    ShippingRate::factory()->inactive()->create(['zone_id' => $zone->id, 'name' => 'Disabled']);

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'US']);

    expect($rates)->toHaveCount(2);
});

it('calculates a flat rate', function () {
    $rate = ShippingRate::factory()->create([
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 799],
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $cost = $this->calculator->calculate($rate, $cart);

    expect($cost)->toBe(799);
});

it('calculates a weight-based rate', function () {
    $rate = ShippingRate::factory()->weightBased()->create();
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $variant = ProductVariant::factory()->create([
        'weight_g' => 200,
        'requires_shipping' => true,
    ]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $cost = $this->calculator->calculate($rate, $cart);

    // 200g * 2 = 400g, falls in range 0-500: amount 499
    expect($cost)->toBe(499);
});

it('calculates a price-based rate', function () {
    $rate = ShippingRate::factory()->priceBased()->create();
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'line_subtotal_amount' => 3000,
    ]);

    $cost = $this->calculator->calculate($rate, $cart);

    // 3000 is in range 0-5000: amount 799
    expect($cost)->toBe(799);
});

it('returns null when weight exceeds all ranges', function () {
    $rate = ShippingRate::factory()->weightBased()->create();
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $variant = ProductVariant::factory()->create([
        'weight_g' => 5000,
        'requires_shipping' => true,
    ]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $cost = $this->calculator->calculate($rate, $cart);

    // 5000g exceeds max range of 2000g
    expect($cost)->toBeNull();
});
