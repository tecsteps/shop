<?php

use App\Enums\PaymentMethod;
use App\Models\Discount;
use App\Services\CheckoutService;
use App\Services\PricingEngine;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(CheckoutService::class);
    $this->pricing = app(PricingEngine::class);
});

it('applies a valid percent discount code at checkout', function () {
    Discount::factory()->for($this->store)->percent(10, 'SAVE10')->create();
    $cart = cartWithLines([[5000, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);

    $this->service->applyDiscountCode($checkout, 'SAVE10');

    expect($checkout->fresh()->totals_json['discount'])->toBe(500);
});

it('applies a valid fixed discount code at checkout', function () {
    Discount::factory()->for($this->store)->fixed(500, '5OFF')->create();
    $cart = cartWithLines([[5000, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);

    $this->service->applyDiscountCode($checkout, '5OFF');

    expect($checkout->fresh()->totals_json['discount'])->toBe(500);
});

it('removes discount when code is cleared', function () {
    Discount::factory()->for($this->store)->percent(10, 'SAVE10')->create();
    $cart = cartWithLines([[5000, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);

    $this->service->applyDiscountCode($checkout, 'SAVE10');
    expect($checkout->fresh()->totals_json['discount'])->toBe(500);

    $this->service->applyDiscountCode($checkout->fresh(), null);
    expect($checkout->fresh()->totals_json['discount'])->toBe(0);
});

it('rejects expired discount at checkout', function () {
    Discount::factory()->for($this->store)->percent(10, 'OLD')->expired()->create();
    $cart = cartWithLines([[5000, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);

    expect(fn () => $this->service->validateDiscountForCheckout('OLD', $checkout))
        ->toThrow(App\Exceptions\InvalidDiscountException::class);
});

it('increments usage count on order completion', function () {
    $discount = Discount::factory()->for($this->store)->percent(10, 'USED')->create(['usage_count' => 5]);
    $cart = cartWithLines([[5000, 1]], ['requires_shipping' => false]);
    $checkout = $this->service->startFromCart($cart);
    $this->service->applyDiscountCode($checkout, 'USED');
    $this->service->setAddress($checkout->fresh(), germanAddressData());
    $this->service->setShippingMethod($checkout->fresh(), null);
    $this->service->selectPaymentMethod($checkout->fresh(), PaymentMethod::CreditCard);

    $this->service->completeCheckout($checkout->fresh(), ['card_number' => '4242424242424242']);

    expect($discount->fresh()->usage_count)->toBe(6);
});

it('handles free shipping discount at checkout', function () {
    Discount::factory()->for($this->store)->freeShipping('FREESHIP')->create();
    $zone = App\Models\ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = App\Models\ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $cart = cartWithLines([[5000, 1]]);
    $checkout = $this->service->startFromCart($cart);
    $this->service->setAddress($checkout, germanAddressData());
    $this->service->setShippingMethod($checkout->fresh(), $rate->id);
    $this->service->applyDiscountCode($checkout->fresh(), 'FREESHIP');

    expect($checkout->fresh()->totals_json['shipping'])->toBe(0);
});
