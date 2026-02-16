<?php

use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;

beforeEach(function () {
    $this->calculator = new ShippingCalculator;
    $this->ctx = createStoreContext();
});

it('matches a zone by country code', function () {
    $zone = ShippingZone::factory()->for($this->ctx['store'])->create([
        'countries_json' => ['DE', 'AT', 'CH'],
        'is_active' => true,
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'amount' => 499, 'is_active' => true]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country_code' => 'DE']);
    expect($rates)->toHaveCount(1);
});

it('returns empty when no zone matches the address', function () {
    ShippingZone::factory()->for($this->ctx['store'])->create([
        'countries_json' => ['DE'],
        'is_active' => true,
    ]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country_code' => 'FR']);
    expect($rates)->toHaveCount(0);
});

it('calculates a flat rate', function () {
    $zone = ShippingZone::factory()->for($this->ctx['store'])->create(['is_active' => true]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'amount' => 499, 'is_active' => true]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $result = $this->calculator->calculate($rate, $cart);

    expect($result)->toBe(499);
});

it('skips inactive rates', function () {
    $zone = ShippingZone::factory()->for($this->ctx['store'])->create([
        'countries_json' => ['US'],
        'is_active' => true,
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'is_active' => false]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country_code' => 'US']);
    expect($rates)->toHaveCount(0);
});

it('returns rates from multiple matching zones', function () {
    $zone1 = ShippingZone::factory()->for($this->ctx['store'])->create(['countries_json' => ['US'], 'is_active' => true]);
    $zone2 = ShippingZone::factory()->for($this->ctx['store'])->create(['countries_json' => ['US'], 'is_active' => true]);
    ShippingRate::factory()->create(['zone_id' => $zone1->id, 'amount' => 499, 'is_active' => true]);
    ShippingRate::factory()->create(['zone_id' => $zone2->id, 'amount' => 999, 'is_active' => true]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country_code' => 'US']);
    expect($rates)->toHaveCount(2);
});
