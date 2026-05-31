<?php

use App\Models\Discount;
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

it('calculates correct totals for a simple checkout', function () {
    TaxSettings::factory()->for($this->store)->rate(1900)->create(); // exclusive

    $cart = cartWithLines([[2500, 2]]);
    $checkout = $this->service->startFromCart($cart);
    $this->service->setAddress($checkout, germanAddressData());

    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();
    $this->service->setShippingMethod($checkout->fresh(), $rate->id);

    $result = $this->pricing->calculate($checkout->fresh());

    // subtotal 5000; shipping 499; tax 19% of (5000 + 499) = intdiv(5499*1900,10000) = 1044; total 6543.
    expect($result->subtotal)->toBe(5000)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(1044)
        ->and($result->total)->toBe(6543);
});

it('applies discount code and recalculates', function () {
    Discount::factory()->for($this->store)->percent(10, 'TEN')->create();

    $cart = cartWithLines([[10000, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);
    $this->service->applyDiscountCode($checkout, 'TEN');

    $result = $this->pricing->calculate($checkout->fresh());

    expect($result->discount)->toBe(1000)
        ->and($result->discountedSubtotal())->toBe(9000);
});

it('stores pricing snapshot in totals_json', function () {
    TaxSettings::factory()->for($this->store)->rate(1900)->create();
    $cart = cartWithLines([[2500, 2]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);

    $this->pricing->calculate($checkout);

    $totals = $checkout->fresh()->totals_json;

    expect($totals)->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax', 'total', 'currency', 'tax_lines']);
});

it('recalculates on shipping method change', function () {
    $cart = cartWithLines([[5000, 1]], ['weight_g' => 750]);
    $checkout = $this->service->startFromCart($cart);
    $this->service->setAddress($checkout, germanAddressData());

    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $flat = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();
    $weight = ShippingRate::factory()->for($zone, 'zone')->weight([
        ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
        ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
    ])->create();

    $this->service->setShippingMethod($checkout->fresh(), $flat->id);
    expect($checkout->fresh()->totals_json['shipping'])->toBe(499);

    $this->service->setShippingMethod($checkout->fresh(), $weight->id);
    expect($checkout->fresh()->totals_json['shipping'])->toBe(899);
});

it('handles prices-include-tax correctly', function () {
    TaxSettings::factory()->for($this->store)->rate(1900)->inclusive()->create();
    $cart = cartWithLines([[11900, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);

    $result = $this->pricing->calculate($checkout->fresh());

    // Gross line subtotal 11900; tax extracted = 1900; net subtotal = 10000.
    expect($result->taxTotal)->toBe(1900)
        ->and($result->subtotal - $result->taxTotal)->toBe(10000)
        ->and($result->total)->toBe(11900);
});
