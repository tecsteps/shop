<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Services\Payments\MockPaymentProvider;

beforeEach(function (): void {
    $this->createStoreContext();
    $this->provider = app(MockPaymentProvider::class);
});

it('returns success for the magic visa card', function (): void {
    $checkout = Checkout::factory()->create(['store_id' => app('current_store')->id]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => MockPaymentProvider::CARD_SUCCESS,
    ]);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe(PaymentStatus::Captured);
    expect($result->providerPaymentId)->toStartWith('mock_');
});

it('declines the magic card', function (): void {
    $checkout = Checkout::factory()->create(['store_id' => app('current_store')->id]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => MockPaymentProvider::CARD_DECLINE,
    ]);

    expect($result->success)->toBeFalse();
    expect($result->errorCode)->toBe('card_declined');
});

it('returns insufficient_funds for the matching magic card', function (): void {
    $checkout = Checkout::factory()->create(['store_id' => app('current_store')->id]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => MockPaymentProvider::CARD_INSUFFICIENT,
    ]);

    expect($result->success)->toBeFalse();
    expect($result->errorCode)->toBe('insufficient_funds');
});

it('paypal always succeeds with instant capture', function (): void {
    $checkout = Checkout::factory()->create(['store_id' => app('current_store')->id]);

    $result = $this->provider->charge($checkout, PaymentMethod::Paypal);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe(PaymentStatus::Captured);
});

it('bank transfer returns pending status', function (): void {
    $checkout = Checkout::factory()->create(['store_id' => app('current_store')->id]);

    $result = $this->provider->charge($checkout, PaymentMethod::BankTransfer);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe(PaymentStatus::Pending);
});

it('refund returns a processed result with provider id', function (): void {
    $payment = \App\Models\Payment::factory()->create();

    $result = $this->provider->refund($payment, 500);

    expect($result->success)->toBeTrue();
    expect($result->providerRefundId)->toStartWith('mock_refund_');
});
