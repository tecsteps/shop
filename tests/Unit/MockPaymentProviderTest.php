<?php

use App\Models\Order;
use App\Services\Payments\MockPaymentProvider;

beforeEach(function () {
    $this->store = bindCurrentStore(makeStore());
    $this->order = Order::create([
        'store_id' => $this->store->id,
        'order_number' => '#1001',
        'status' => 'open',
        'financial_status' => 'pending',
        'fulfillment_status' => 'unfulfilled',
        'currency' => 'EUR',
        'subtotal_amount' => 1000,
        'total_amount' => 1000,
        'email' => 'x@y.test',
        'placed_at' => now(),
    ]);
});

it('succeeds with the success magic card', function () {
    $result = app(MockPaymentProvider::class)->charge($this->order, [
        'method' => 'credit_card',
        'card_number' => MockPaymentProvider::MAGIC_SUCCESS,
    ]);

    expect($result->succeeded)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

it('fails with the decline magic card', function () {
    $result = app(MockPaymentProvider::class)->charge($this->order, [
        'method' => 'credit_card',
        'card_number' => MockPaymentProvider::MAGIC_DECLINED,
    ]);

    expect($result->succeeded)->toBeFalse()
        ->and($result->error)->toBe('card_declined');
});

it('authorizes bank transfer payments', function () {
    $result = app(MockPaymentProvider::class)->charge($this->order, [
        'method' => 'bank_transfer',
    ]);

    expect($result->succeeded)->toBeTrue()
        ->and($result->status)->toBe('authorized');
});
