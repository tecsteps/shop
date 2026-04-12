<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Store;
use App\Services\Payments\MockPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->provider = new MockPaymentProvider;
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

function buildCheckout(Store $store, int $total = 5000, string $currency = 'EUR'): Checkout
{
    $cart = \App\Models\Cart::factory()->for($store)->create(['currency' => $currency]);

    return Checkout::factory()->for($store)->for($cart)->create([
        'payment_method' => PaymentMethod::CreditCard->value,
        'totals_json' => ['total' => $total, 'currency' => $currency],
    ]);
}

it('captures a credit card payment with success magic number', function (): void {
    $checkout = buildCheckout($this->store);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($result->status)->toBe(PaymentStatus::Captured)
        ->and($result->providerPaymentId)->toStartWith('mock_')
        ->and($result->amount)->toBe(5000)
        ->and($result->currency)->toBe('EUR');
});

it('fails a credit card payment when magic number is declined', function (): void {
    $checkout = buildCheckout($this->store);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000000002',
    ]);

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->errorMessage)->toBe('card_declined')
        ->and($result->providerPaymentId)->toBeNull();
});

it('fails a credit card payment when insufficient funds', function (): void {
    $checkout = buildCheckout($this->store);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000009995',
    ]);

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->errorMessage)->toBe('insufficient_funds')
        ->and($result->providerPaymentId)->toBeNull();
});

it('captures a paypal payment', function (): void {
    $checkout = buildCheckout($this->store);

    $result = $this->provider->charge($checkout, PaymentMethod::Paypal, []);

    expect($result->status)->toBe(PaymentStatus::Captured)
        ->and($result->providerPaymentId)->toStartWith('mock_');
});

it('returns pending for a bank transfer payment', function (): void {
    $checkout = buildCheckout($this->store);

    $result = $this->provider->charge($checkout, PaymentMethod::BankTransfer, []);

    expect($result->status)->toBe(PaymentStatus::Pending)
        ->and($result->providerPaymentId)->toStartWith('mock_');
});
