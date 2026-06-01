<?php

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CheckoutService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(CheckoutService::class);
});

it('transitions from started to addressed with valid address', function () {
    $checkout = startCheckout();

    $this->service->setAddress($checkout, germanAddressData());

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Addressed);
});

it('rejects address transition with missing required fields', function () {
    $checkout = startCheckout();
    $data = germanAddressData();
    unset($data['shipping_address']['city']);

    expect(fn () => $this->service->setAddress($checkout, $data))->toThrow(ValidationException::class);
    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Started);
});

it('transitions from addressed to shipping_selected', function () {
    $checkout = startCheckout();
    $this->service->setAddress($checkout, germanAddressData());

    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $this->service->setShippingMethod($checkout->fresh(), $rate->id);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->fresh()->shipping_method_id)->toBe($rate->id);
});

it('rejects shipping selection with rate from wrong zone', function () {
    $checkout = startCheckout();
    $this->service->setAddress($checkout, germanAddressData());

    $usZone = ShippingZone::factory()->for($this->store)->countries(['US'])->create();
    $usRate = ShippingRate::factory()->for($usZone, 'zone')->flat(599)->create();

    expect(fn () => $this->service->setShippingMethod($checkout->fresh(), $usRate->id))
        ->toThrow(ValidationException::class);
});

it('skips shipping selection when no items require shipping', function () {
    $checkout = startCheckout(['requires_shipping' => false]);
    $this->service->setAddress($checkout, germanAddressData());

    $this->service->setShippingMethod($checkout->fresh(), null);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->fresh()->shipping_method_id)->toBeNull()
        ->and($checkout->fresh()->totals_json['shipping'])->toBe(0);
});

it('transitions from shipping_selected to payment_selected', function () {
    $checkout = startCheckout(['requires_shipping' => false]);
    $this->service->setAddress($checkout, germanAddressData());
    $this->service->setShippingMethod($checkout->fresh(), null);

    $this->service->selectPaymentMethod($checkout->fresh(), PaymentMethod::CreditCard);

    $fresh = $checkout->fresh();
    $item = $fresh->cart->lines->first()->variant->inventoryItem;

    expect($fresh->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($fresh->expires_at)->not->toBeNull()
        ->and($item->quantity_reserved)->toBe(1);
});

it('transitions from payment_selected to completed', function () {
    $checkout = startCheckout(['requires_shipping' => false]);
    $this->service->setAddress($checkout, germanAddressData());
    $this->service->setShippingMethod($checkout->fresh(), null);
    $this->service->selectPaymentMethod($checkout->fresh(), PaymentMethod::CreditCard);

    $order = $this->service->completeCheckout($checkout->fresh(), ['card_number' => '4242424242424242']);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Completed)
        ->and($order)->not->toBeNull();
});

it('rejects invalid state transitions', function () {
    $checkout = startCheckout();

    expect(fn () => $this->service->completeCheckout($checkout))
        ->toThrow(InvalidCheckoutTransitionException::class);
});

it('recalculates pricing on address change', function () {
    $checkout = startCheckout(['requires_shipping' => false]);
    TaxSettings::factory()->for($this->store)->rate(1900)->create();

    $this->service->setAddress($checkout, germanAddressData());
    $firstTax = $checkout->fresh()->totals_json['tax'];

    $this->service->setAddress($checkout->fresh(), germanAddressData());
    $secondTax = $checkout->fresh()->totals_json['tax'];

    expect($firstTax)->toBe($secondTax)
        ->and($firstTax)->toBeGreaterThan(0);
});
