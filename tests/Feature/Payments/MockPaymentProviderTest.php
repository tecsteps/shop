<?php

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\MockPaymentProvider;

it('charges credit card successfully with magic success number', function () {
    $checkout = Checkout::factory()->create(['status' => CheckoutStatus::PaymentSelected]);
    $provider = new MockPaymentProvider;

    $result = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured')
        ->and($result->referenceId)->toStartWith('mock_');
});

it('declines credit card with decline magic number', function () {
    $checkout = Checkout::factory()->create(['status' => CheckoutStatus::PaymentSelected]);
    $provider = new MockPaymentProvider;

    $result = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000000002',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('card_declined');
});

it('returns insufficient funds for magic number', function () {
    $checkout = Checkout::factory()->create(['status' => CheckoutStatus::PaymentSelected]);
    $provider = new MockPaymentProvider;

    $result = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000009995',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('insufficient_funds');
});

it('handles card numbers with spaces', function () {
    $checkout = Checkout::factory()->create(['status' => CheckoutStatus::PaymentSelected]);
    $provider = new MockPaymentProvider;

    $result = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000 0000 0000 0002',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('card_declined');
});

it('succeeds with any other card number', function () {
    $checkout = Checkout::factory()->create(['status' => CheckoutStatus::PaymentSelected]);
    $provider = new MockPaymentProvider;

    $result = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '5555555555554444',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

it('always succeeds for PayPal', function () {
    $checkout = Checkout::factory()->create(['status' => CheckoutStatus::PaymentSelected]);
    $provider = new MockPaymentProvider;

    $result = $provider->charge($checkout, PaymentMethod::Paypal, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

it('returns pending for bank transfer', function () {
    $checkout = Checkout::factory()->create(['status' => CheckoutStatus::PaymentSelected]);
    $provider = new MockPaymentProvider;

    $result = $provider->charge($checkout, PaymentMethod::BankTransfer, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('pending');
});

it('generates unique reference IDs', function () {
    $checkout = Checkout::factory()->create(['status' => CheckoutStatus::PaymentSelected]);
    $provider = new MockPaymentProvider;

    $result1 = $provider->charge($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);
    $result2 = $provider->charge($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($result1->referenceId)->not->toBe($result2->referenceId);
});

it('processes refunds successfully', function () {
    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Captured,
        'amount' => 5000,
    ]);

    $provider = new MockPaymentProvider;
    $result = $provider->refund($payment, 2500);

    expect($result->success)->toBeTrue()
        ->and($result->referenceId)->toStartWith('mock_refund_');
});
