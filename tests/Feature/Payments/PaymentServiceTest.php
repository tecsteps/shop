<?php

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Services\CheckoutService;
use App\Services\Payment\MockPaymentProvider;
use App\Services\PaymentService;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function paidCheckoutContext(): array
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 2]]);
    $rate = CommerceTestHelper::flatShippingRate($store);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);

    return ['store' => $store, 'cart' => $cart, 'checkout' => $checkout];
}

it('processes credit card payment and creates order as paid', function () {
    $ctx = paidCheckoutContext();

    $checkout = app(CheckoutService::class)->selectPaymentMethod($ctx['checkout'], PaymentMethod::CreditCard);
    $order = app(CheckoutService::class)->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($order->financial_status)->toBe(FinancialStatus::Paid);

    $item = App\Models\InventoryItem::query()
        ->where('variant_id', $ctx['cart']->cartLines()->first()->variant_id)
        ->first();
    expect($item->quantity_on_hand)->toBe(48);
    expect($item->quantity_reserved)->toBe(0);
});

it('processes PayPal payment and creates order as paid', function () {
    $ctx = paidCheckoutContext();

    $checkout = app(CheckoutService::class)->selectPaymentMethod($ctx['checkout'], PaymentMethod::Paypal);
    $order = app(CheckoutService::class)->pay($checkout, PaymentMethod::Paypal, []);

    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->payment_method)->toBe(PaymentMethod::Paypal);
});

it('processes bank transfer and creates order as pending', function () {
    $ctx = paidCheckoutContext();

    $checkout = app(CheckoutService::class)->selectPaymentMethod($ctx['checkout'], PaymentMethod::BankTransfer);
    $order = app(CheckoutService::class)->pay($checkout, PaymentMethod::BankTransfer, []);

    expect($order->financial_status)->toBe(FinancialStatus::Pending);
    expect($order->payment_method)->toBe(PaymentMethod::BankTransfer);

    $item = App\Models\InventoryItem::query()
        ->where('variant_id', $ctx['cart']->cartLines()->first()->variant_id)
        ->first();
    expect($item->quantity_on_hand)->toBe(50);
    expect($item->quantity_reserved)->toBe(2);
});

it('resolves MockPaymentProvider from container', function () {
    expect(app(PaymentProvider::class))->toBeInstanceOf(MockPaymentProvider::class);
    expect(app(PaymentService::class))->toBeInstanceOf(PaymentService::class);
});

it('creates a payment record with correct method', function () {
    $ctx = paidCheckoutContext();

    $checkout = app(CheckoutService::class)->selectPaymentMethod($ctx['checkout'], PaymentMethod::CreditCard);
    $order = app(CheckoutService::class)->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    $payment = $order->payments()->first();

    expect($payment->method)->toBe(PaymentMethod::CreditCard);
    expect($payment->provider->value)->toBe('mock');
    expect($payment->provider_payment_id)->toStartWith('mock_');
    expect($payment->status->value)->toBe('captured');
});
