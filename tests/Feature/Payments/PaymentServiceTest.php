<?php

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Services\CheckoutService;
use App\Services\Payment\MockPaymentProvider;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(CheckoutService::class);
});

/**
 * Drive a checkout to payment_selected for the given method, then complete it.
 */
function completeWith(CheckoutService $service, PaymentMethod $method, array $details = [], bool $digital = false): App\Models\Order
{
    $checkout = startCheckout(['requires_shipping' => ! $digital]);
    $service->setAddress($checkout, germanAddressData());

    if ($digital) {
        $service->setShippingMethod($checkout->fresh(), null);
    } else {
        $zone = App\Models\ShippingZone::factory()->for(app('current_store'))->countries(['DE'])->create();
        $rate = App\Models\ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();
        $service->setShippingMethod($checkout->fresh(), $rate->id);
    }

    $service->selectPaymentMethod($checkout->fresh(), $method);

    return $service->completeCheckout($checkout->fresh(), $details);
}

it('processes credit card payment and creates order as paid', function () {
    $order = completeWith($this->service, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    $item = $order->lines->first()->variant->inventoryItem->fresh();

    expect($order->financial_status->value)->toBe('paid')
        ->and($item->quantity_on_hand)->toBe(99)
        ->and($item->quantity_reserved)->toBe(0);
});

it('processes PayPal payment and creates order as paid', function () {
    $order = completeWith($this->service, PaymentMethod::Paypal);

    expect($order->financial_status->value)->toBe('paid');
});

it('processes bank transfer and creates order as pending', function () {
    $order = completeWith($this->service, PaymentMethod::BankTransfer);

    $item = $order->lines->first()->variant->inventoryItem->fresh();

    expect($order->financial_status->value)->toBe('pending')
        // bank transfer keeps inventory reserved, not committed.
        ->and($item->quantity_on_hand)->toBe(100)
        ->and($item->quantity_reserved)->toBe(1);
});

it('resolves MockPaymentProvider from container', function () {
    expect(app(PaymentProvider::class))->toBeInstanceOf(MockPaymentProvider::class);
});

it('creates a payment record with correct method', function () {
    $order = completeWith($this->service, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    $payment = $order->payments->first();

    expect($payment->method)->toBe(PaymentMethod::CreditCard)
        ->and($payment->provider)->toBe('mock')
        ->and($payment->status->value)->toBe('captured');
});
