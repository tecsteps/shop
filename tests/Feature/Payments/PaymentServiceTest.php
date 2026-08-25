<?php

use App\Contracts\PaymentProvider;
use App\Services\Payments\MockPaymentProvider;

it('resolves MockPaymentProvider from container', function () {
    expect(app(PaymentProvider::class))->toBeInstanceOf(MockPaymentProvider::class);
});

it('processes credit card payment and creates order as paid', function () {
    $order = makeCompletedOrder();

    expect($order->financial_status)->toBe('paid');
    expect($order->payments()->first()->status)->toBe('captured');
});

it('creates a payment record with correct method', function () {
    $order = makeCompletedOrder();
    $payment = $order->payments()->first();

    expect($payment->method)->toBe('credit_card');
    expect($payment->provider)->toBe('mock');
});
