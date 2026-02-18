<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Services\Payment\MockPaymentProvider;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->provider = new MockPaymentProvider;
});

it('charges credit card with success card number', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => \App\Models\Cart::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Captured);
});

it('declines credit card with decline card number', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => \App\Models\Cart::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000000002',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('card_declined');
});

it('returns insufficient funds for that card number', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => \App\Models\Cart::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000009995',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('insufficient_funds');
});

it('charges PayPal successfully', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => \App\Models\Cart::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::Paypal, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Captured);
});

it('creates pending payment for bank transfer', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => \App\Models\Cart::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::BankTransfer, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Pending);
});

it('generates mock reference ID', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => \App\Models\Cart::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($result->providerPaymentId)->toStartWith('mock_');
});
