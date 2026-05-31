<?php

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(CheckoutService::class);
    $this->shipping = app(ShippingCalculator::class);
});

it('returns available shipping rates for address', function () {
    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    ShippingRate::factory()->for($zone, 'zone')->flat(499)->create(['name' => 'Standard']);

    $rates = $this->shipping->getAvailableRates($this->store, ['country' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Standard')
        ->and($rates->first()->config_json['amount'])->toBe(499);
});

it('returns empty when no zone matches address', function () {
    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    expect($this->shipping->getAvailableRates($this->store, ['country' => 'FR']))->toBeEmpty();
});

it('calculates flat rate correctly', function () {
    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $cart = cartWithLines([[5000, 1]]);
    $checkout = $this->service->startFromCart($cart);
    $this->service->setAddress($checkout, germanAddressData());
    $this->service->setShippingMethod($checkout->fresh(), $rate->id);

    expect($checkout->fresh()->totals_json['shipping'])->toBe(499);
});

it('calculates weight-based rate correctly', function () {
    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->weight([
        ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
        ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
    ])->create();

    $cart = cartWithLines([[5000, 1]], ['weight_g' => 750]);
    $checkout = $this->service->startFromCart($cart);
    $this->service->setAddress($checkout, germanAddressData());
    $this->service->setShippingMethod($checkout->fresh(), $rate->id);

    expect($checkout->fresh()->totals_json['shipping'])->toBe(899);
});

it('returns zero shipping when all items are digital', function () {
    $cart = cartWithLines([[5000, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);
    $this->service->setAddress($checkout, germanAddressData());
    $this->service->setShippingMethod($checkout->fresh(), null);

    expect($checkout->fresh()->totals_json['shipping'])->toBe(0);
});
