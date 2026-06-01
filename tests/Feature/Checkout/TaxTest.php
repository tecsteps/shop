<?php

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CheckoutService;
use App\Services\PricingEngine;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(CheckoutService::class);
    $this->pricing = app(PricingEngine::class);
});

it('calculates exclusive tax correctly at checkout', function () {
    TaxSettings::factory()->for($this->store)->rate(1900)->create(); // exclusive

    // discounted subtotal 5000 + shipping 499 = 5499 taxable.
    $cart = cartWithLines([[5000, 1]]);
    $checkout = $this->service->startFromCart($cart);
    $this->service->setAddress($checkout, germanAddressData());

    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();
    $this->service->setShippingMethod($checkout->fresh(), $rate->id);

    expect($checkout->fresh()->totals_json['tax'])->toBe(1044);
});

it('extracts inclusive tax correctly at checkout', function () {
    TaxSettings::factory()->for($this->store)->rate(1900)->inclusive()->create();

    $cart = cartWithLines([[11900, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);

    expect($checkout->fresh()->totals_json['tax'])->toBe(1900);
});

it('applies zero tax when no tax settings exist', function () {
    $cart = cartWithLines([[5000, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);

    expect($checkout->fresh()->totals_json['tax'])->toBe(0);
});

it('stores tax lines in totals_json', function () {
    TaxSettings::factory()->for($this->store)->rate(1900, 'VAT')->create();
    $cart = cartWithLines([[5000, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);

    $taxLines = $checkout->fresh()->totals_json['tax_lines'];

    expect($taxLines)->toHaveCount(1)
        ->and($taxLines[0])->toHaveKeys(['name', 'rate', 'amount'])
        ->and($taxLines[0]['rate'])->toBe(1900);
});
