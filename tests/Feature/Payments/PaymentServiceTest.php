<?php

use App\Contracts\PaymentProvider;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Models\Checkout;
use App\Services\OrderService;
use App\Services\Payments\MockPaymentProvider;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('processes credit card payment and creates order as paid', function () {
    ['cart' => $cart] = createCartWithProduct($this->ctx['store'], 2500, 1, 100);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'credit_card',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500],
    ]);

    $order = app(OrderService::class)->createFromCheckout($checkout);

    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payments)->toHaveCount(1);
});

it('processes bank transfer and creates order as pending', function () {
    ['cart' => $cart] = createCartWithProduct($this->ctx['store'], 2500, 1, 100);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'bank_transfer',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500],
    ]);

    $order = app(OrderService::class)->createFromCheckout($checkout);

    expect($order->financial_status)->toBe(FinancialStatus::Pending);
});

it('resolves MockPaymentProvider from container', function () {
    $provider = app(PaymentProvider::class);
    expect($provider)->toBeInstanceOf(MockPaymentProvider::class);
});

it('creates a payment record with correct method', function () {
    ['cart' => $cart] = createCartWithProduct($this->ctx['store'], 2500, 1, 100);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'credit_card',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500],
    ]);

    $order = app(OrderService::class)->createFromCheckout($checkout);
    $payment = $order->payments->first();

    expect($payment->method->value)->toBe('credit_card')
        ->and($payment->provider)->toBe('mock');
});
