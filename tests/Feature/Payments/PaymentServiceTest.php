<?php

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Services\CheckoutService;
use App\Services\Payments\MockPaymentProvider;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
});

it('processes credit card payment and creates order as paid', function () {
    $checkout = createPaymentSelectedCheckout($this->store, 'credit_card', quantity: 2, quantityOnHand: 10);

    $order = $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->financial_status)->toBe(FinancialStatus::Paid);

    $item = $checkout->cart->lines()->first()->variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(8);
    expect($item->quantity_reserved)->toBe(0);
});

it('processes PayPal payment and creates order as paid', function () {
    $checkout = createPaymentSelectedCheckout($this->store, 'paypal', quantity: 2, quantityOnHand: 10);

    $order = $this->checkoutService->completeCheckout($checkout);

    expect($order->financial_status)->toBe(FinancialStatus::Paid);

    $item = $checkout->cart->lines()->first()->variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(8);
    expect($item->quantity_reserved)->toBe(0);
});

it('processes bank transfer and creates order as pending', function () {
    $checkout = createPaymentSelectedCheckout($this->store, 'bank_transfer', quantity: 2, quantityOnHand: 10);

    $order = $this->checkoutService->completeCheckout($checkout);

    expect($order->financial_status)->toBe(FinancialStatus::Pending);

    $item = $checkout->cart->lines()->first()->variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(10);
    expect($item->quantity_reserved)->toBe(2);
});

it('resolves MockPaymentProvider from container', function () {
    expect(app(PaymentProvider::class))->toBeInstanceOf(MockPaymentProvider::class);
});

it('creates a payment record with correct method', function () {
    $checkout = createPaymentSelectedCheckout($this->store, 'credit_card');

    $order = $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->getKey(),
        'provider' => 'mock',
        'method' => 'credit_card',
        'status' => 'captured',
        'amount' => $order->total_amount,
    ]);
});
