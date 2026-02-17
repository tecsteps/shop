<?php

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Store;
use App\Services\Payments\MockPaymentProvider;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $this->checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'totals_json' => ['subtotal' => 5000, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 5000, 'currency' => 'EUR'],
    ]);
    $this->provider = new MockPaymentProvider;
});

it('charges credit card successfully with magic success number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured')
        ->and($result->providerPaymentId)->toStartWith('mock_');
});

it('declines credit card with decline magic number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000000002',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe('failed')
        ->and($result->errorCode)->toBe('card_declined');
});

it('returns insufficient funds for insufficient funds magic number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000009995',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe('failed')
        ->and($result->errorCode)->toBe('insufficient_funds');
});

it('succeeds for any other card number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, [
        'card_number' => '5555555555554444',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

it('always succeeds for paypal', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::Paypal, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured')
        ->and($result->providerPaymentId)->toStartWith('mock_');
});

it('returns pending for bank transfer', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::BankTransfer, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('pending')
        ->and($result->providerPaymentId)->toStartWith('mock_');
});
