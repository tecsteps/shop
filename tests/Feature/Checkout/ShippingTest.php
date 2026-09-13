<?php

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns available shipping rates for address', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 499);

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'DE'], $cart);

    expect($rates)->toHaveCount(1);
    expect($rates->first()->id)->toBe($rate->id);
    expect($rates->first()->amount)->toBe(499);
});

it('returns empty when no zone matches address', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    CommerceTestHelper::flatShippingRate($store, ['DE'], 499);

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'FR'], $cart);

    expect($rates)->toBeEmpty();
});

it('calculates flat rate correctly', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(499);
});

it('calculates weight-based rate correctly', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 3, 'weight' => 250]]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Weight,
        'config_json' => ['ranges' => [
            ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
            ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
        ]],
        'is_active' => true,
    ]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(899);
});

it('returns zero shipping when all items are digital', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 1, 'shipping' => false]]);
    CommerceTestHelper::flatShippingRate($store, ['DE'], 499);

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'DE'], $cart);

    expect($rates)->toBeEmpty();
});
