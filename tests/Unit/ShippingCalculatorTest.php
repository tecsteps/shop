<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('matches a zone by country code', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE', 'AT', 'CH']]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->create();

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'DE']);

    expect($rates)->toHaveCount(1);
    expect($rates->first()->getKey())->toBe($rate->getKey());
});

it('matches a zone by region code', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
        'regions_json' => ['US-NY', 'US-CA'],
    ]);
    ShippingRate::factory()->for($zone, 'zone')->create();

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, [
        'country_code' => 'US',
        'province_code' => 'US-NY',
    ]);

    expect($rates)->toHaveCount(1);
});

it('returns empty when no zone matches the address', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE']]);
    ShippingRate::factory()->for($zone, 'zone')->create();

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'FR']);

    expect($rates)->toBeEmpty();
});

it('calculates a flat rate', function () {
    $store = Store::factory()->create();
    $variant = createPurchasableVariant($store);
    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->for($cart)->priced(2500, 1)->create(['variant_id' => $variant->getKey()]);

    $rate = ShippingRate::factory()->flatAmount(499)->create();

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(499);
});

it('calculates a weight-based rate', function () {
    $store = Store::factory()->create();
    $variant = createPurchasableVariant($store, 2500, 100, ['weight_g' => 250]);
    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->for($cart)->priced(2500, 3)->create(['variant_id' => $variant->getKey()]);

    $rate = ShippingRate::factory()->create([
        'type' => 'weight',
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
            ],
        ],
    ]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(899);
});

it('calculates a price-based rate', function () {
    $store = Store::factory()->create();
    $variant = createPurchasableVariant($store, 7500);
    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->for($cart)->priced(7500, 1)->create(['variant_id' => $variant->getKey()]);

    $rate = ShippingRate::factory()->create([
        'type' => 'price',
        'config_json' => [
            'ranges' => [
                ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
                ['min_amount' => 5001, 'max_amount' => 999999, 'amount' => 399],
            ],
        ],
    ]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(399);
});

it('returns zero shipping when no items require shipping', function () {
    $store = Store::factory()->create();
    $variant = createPurchasableVariant($store, 2500, 100, ['requires_shipping' => false]);
    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->for($cart)->priced(2500, 1)->create(['variant_id' => $variant->getKey()]);

    $rate = ShippingRate::factory()->flatAmount(499)->create();

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(0);
});

it('returns the correct rate when multiple zones match and the first is selected', function () {
    $store = Store::factory()->create();

    $zoneA = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE']]);
    $rateA = ShippingRate::factory()->for($zoneA, 'zone')->flatAmount(499)->create();

    $zoneB = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE', 'AT']]);
    $rateB = ShippingRate::factory()->for($zoneB, 'zone')->flatAmount(999)->create();

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'DE']);

    expect($rates->pluck('id')->all())->toContain($rateA->getKey(), $rateB->getKey());
});

it('skips inactive rates', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE']]);
    ShippingRate::factory()->for($zone, 'zone')->inactive()->create();
    $activeRate = ShippingRate::factory()->for($zone, 'zone')->create();

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'DE']);

    expect($rates->pluck('id')->all())->toBe([$activeRate->getKey()]);
});
