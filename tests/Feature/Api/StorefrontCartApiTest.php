<?php

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->seed();
});

test('storefront cart api creates carts and mutates lines with version conflicts', function () {
    $variant = Product::query()->where('handle', 'linen-shirt')->firstOrFail()->variants()->firstOrFail();

    $cartId = $this->postJson('http://shop.test/api/storefront/v1/carts')
        ->assertCreated()
        ->assertJsonPath('data.currency', 'EUR')
        ->json('data.id');

    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cartId}/lines", [
        'variant_id' => $variant->id,
        'quantity' => 2,
        'cart_version' => 1,
    ])
        ->assertCreated()
        ->assertJsonPath('data.cart_version', 2)
        ->assertJsonPath('data.totals.item_count', 2);

    $lineId = $this->getJson("http://shop.test/api/storefront/v1/carts/{$cartId}")
        ->assertOk()
        ->json('data.lines.0.id');

    $this->putJson("http://shop.test/api/storefront/v1/carts/{$cartId}/lines/{$lineId}", [
        'quantity' => 3,
        'cart_version' => 1,
    ])->assertConflict();

    $this->putJson("http://shop.test/api/storefront/v1/carts/{$cartId}/lines/{$lineId}", [
        'quantity' => 3,
        'cart_version' => 2,
    ])
        ->assertOk()
        ->assertJsonPath('data.cart_version', 3)
        ->assertJsonPath('data.totals.item_count', 3);
});

test('storefront checkout api advances address shipping discount and payment method', function () {
    $variant = Product::query()->where('handle', 'linen-shirt')->firstOrFail()->variants()->firstOrFail();
    $cartId = $this->postJson('http://shop.test/api/storefront/v1/carts')->json('data.id');

    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cartId}/lines", [
        'variant_id' => $variant->id,
        'quantity' => 1,
        'cart_version' => 1,
    ])->assertCreated();

    $checkoutId = $this->postJson('http://shop.test/api/storefront/v1/checkouts', [
        'cart_id' => $cartId,
        'email' => 'buyer@example.com',
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'started')
        ->json('data.id');

    $addressResponse = $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/address", [
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
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'addressed');

    $shippingRateId = $addressResponse->json('data.available_shipping_methods.0.id');

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/shipping-method", [
        'shipping_method_id' => $shippingRateId,
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'shipping_selected')
        ->assertJsonPath('data.totals.shipping', 500);

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/apply-discount", [
        'code' => 'welcome10',
    ])
        ->assertOk()
        ->assertJsonPath('data.discount_code', 'WELCOME10')
        ->assertJsonPath('data.totals.discount', 500);

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/payment-method", [
        'payment_method' => 'credit_card',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'payment_selected')
        ->assertJsonPath('data.payment_method', 'credit_card');
});
