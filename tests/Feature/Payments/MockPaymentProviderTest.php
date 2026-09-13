<?php

use App\Enums\PaymentMethod;
use App\Services\Payment\MockPaymentProvider;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function paymentCheckoutContext(): App\Models\Checkout
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);

    return App\Models\Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => App\Enums\CheckoutStatus::PaymentSelected,
    ]);
}

it('charges credit card with success card number', function () {
    $result = app(MockPaymentProvider::class)->charge(
        paymentCheckoutContext(),
        PaymentMethod::CreditCard,
        ['card_number' => '4242424242424242']
    );

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
});

it('declines credit card with decline card number', function () {
    $result = app(MockPaymentProvider::class)->charge(
        paymentCheckoutContext(),
        PaymentMethod::CreditCard,
        ['card_number' => '4000000000000002']
    );

    expect($result->success)->toBeFalse();
    expect($result->errorCode)->toBe('card_declined');
});

it('returns insufficient funds for that card number', function () {
    $result = app(MockPaymentProvider::class)->charge(
        paymentCheckoutContext(),
        PaymentMethod::CreditCard,
        ['card_number' => '4000000000009995']
    );

    expect($result->success)->toBeFalse();
    expect($result->errorCode)->toBe('insufficient_funds');
});

it('charges PayPal successfully', function () {
    $result = app(MockPaymentProvider::class)->charge(
        paymentCheckoutContext(),
        PaymentMethod::Paypal,
        []
    );

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
});

it('creates pending payment for bank transfer', function () {
    $result = app(MockPaymentProvider::class)->charge(
        paymentCheckoutContext(),
        PaymentMethod::BankTransfer,
        []
    );

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('pending');
});

it('generates mock reference ID', function () {
    $result = app(MockPaymentProvider::class)->charge(
        paymentCheckoutContext(),
        PaymentMethod::CreditCard,
        ['card_number' => '4242424242424242']
    );

    expect($result->referenceId)->toStartWith('mock_');
});
