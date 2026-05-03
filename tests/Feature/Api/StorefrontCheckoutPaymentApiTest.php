<?php

use App\Models\Checkout;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->seed();
});

function apiCheckoutReadyForPayment(TestCase $test): int
{
    $variant = Product::query()->where('handle', 'linen-shirt')->firstOrFail()->variants()->firstOrFail();
    $cartId = $test->postJson('http://shop.test/api/storefront/v1/carts')->json('data.id');

    $test->postJson("http://shop.test/api/storefront/v1/carts/{$cartId}/lines", [
        'variant_id' => $variant->id,
        'quantity' => 1,
        'cart_version' => 1,
    ])->assertCreated();

    $checkoutId = $test->postJson('http://shop.test/api/storefront/v1/checkouts', [
        'cart_id' => $cartId,
        'email' => 'api-buyer@example.com',
    ])->assertCreated()->json('data.id');

    $addressResponse = $test->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/address", [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Street 1',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'use_shipping_as_billing' => true,
    ])->assertOk();

    $test->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/shipping-method", [
        'shipping_method_id' => $addressResponse->json('data.available_shipping_methods.0.id'),
    ])->assertOk();

    return $checkoutId;
}

test('storefront pay endpoint captures credit card payment and returns order', function () {
    $checkoutId = apiCheckoutReadyForPayment($this);

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('order.status', 'paid')
        ->assertJsonPath('order.financial_status', 'paid')
        ->assertJsonPath('order.payment_method', 'credit_card');

    expect(Checkout::withoutGlobalScopes()->findOrFail($checkoutId)->order)->not->toBeNull();
});

test('storefront pay endpoint returns decline code and leaves checkout open', function () {
    $checkoutId = apiCheckoutReadyForPayment($this);

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4000000000000002',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'card_declined');

    $checkout = Checkout::withoutGlobalScopes()->findOrFail($checkoutId);

    expect($checkout->status)->toBe(\App\Enums\CheckoutStatus::ShippingSelected)
        ->and($checkout->order)->toBeNull();
});

test('storefront pay endpoint creates pending bank transfer order with instructions', function () {
    $checkoutId = apiCheckoutReadyForPayment($this);

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/pay", [
        'payment_method' => 'bank_transfer',
    ])
        ->assertOk()
        ->assertJsonPath('order.status', 'pending')
        ->assertJsonPath('order.financial_status', 'pending')
        ->assertJsonPath('bank_transfer_instructions.bank_name', 'Mock Bank AG');
});
