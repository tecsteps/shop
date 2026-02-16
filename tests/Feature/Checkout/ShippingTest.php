<?php

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->calculator = app(ShippingCalculator::class);
});

it('returns available shipping rates for address', function () {
    $zone = ShippingZone::factory()->for($this->ctx['store'])->create([
        'countries_json' => ['DE'],
        'is_active' => true,
    ]);
    ShippingRate::factory()->for($zone, 'zone')->create(['name' => 'Standard', 'amount' => 499, 'is_active' => true]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country_code' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Standard')
        ->and($rates->first()->amount)->toBe(499);
});

it('returns empty when no zone matches address', function () {
    ShippingZone::factory()->for($this->ctx['store'])->create([
        'countries_json' => ['DE'],
        'is_active' => true,
    ]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country_code' => 'FR']);
    expect($rates)->toHaveCount(0);
});

it('calculates flat rate correctly', function () {
    $zone = ShippingZone::factory()->for($this->ctx['store'])->create(['is_active' => true]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->create(['amount' => 499, 'is_active' => true]);

    $cart = \App\Models\Cart::factory()->for($this->ctx['store'])->create();
    $result = $this->calculator->calculate($rate, $cart);

    expect($result)->toBe(499);
});
