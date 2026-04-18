<?php

use App\Contracts\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Services\Payments\MockPaymentProvider;

it('resolves the PaymentProvider contract to the mock implementation', function (): void {
    $provider = app(PaymentProvider::class);

    expect($provider)->toBeInstanceOf(MockPaymentProvider::class);
});

it('can charge via the contract for credit card success', function (): void {
    $this->createStoreContext();
    $checkout = \App\Models\Checkout::factory()->create(['store_id' => app('current_store')->id]);

    $result = app(PaymentProvider::class)->charge($checkout, \App\Enums\PaymentMethod::CreditCard, [
        'card_number' => MockPaymentProvider::CARD_SUCCESS,
    ]);

    expect($result->status)->toBe(PaymentStatus::Captured);
});

it('produces unique mock payment ids per charge', function (): void {
    $this->createStoreContext();
    $checkout = \App\Models\Checkout::factory()->create(['store_id' => app('current_store')->id]);

    $a = app(PaymentProvider::class)->charge($checkout, \App\Enums\PaymentMethod::Paypal);
    $b = app(PaymentProvider::class)->charge($checkout, \App\Enums\PaymentMethod::Paypal);

    expect($a->providerPaymentId)->not->toBe($b->providerPaymentId);
});
