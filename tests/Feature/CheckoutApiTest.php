<?php

use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;

it('uses the shipping address for billing by default in the storefront API', function () {
    $store = shopStore();
    $product = shopProduct($store);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $product['variant'], 1);
    $checkout = app(CheckoutService::class)->start($cart);
    $shippingAddress = [
        'first_name' => 'Alex',
        'last_name' => 'Buyer',
        'address1' => '1 Test Street',
        'city' => 'Berlin',
        'country' => 'Germany',
        'country_code' => 'DE',
        'postal_code' => '10115',
    ];

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout->id}/address", [
        'email' => 'buyer@example.test',
        'shipping_address' => $shippingAddress,
        'billing_address' => [
            'first_name' => 'Different',
            'last_name' => 'Billing',
            'address1' => '2 Another Road',
            'city' => 'Hamburg',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '20095',
        ],
    ])->assertOk();

    expect($checkout->refresh()->billing_address_json)->toBe($checkout->refresh()->shipping_address_json);
});

it('rejects unknown ISO country codes in checkout addresses', function () {
    $store = shopStore();
    $product = shopProduct($store);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $product['variant'], 1);
    $checkout = app(CheckoutService::class)->start($cart);

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout->id}/address", [
        'email' => 'buyer@example.test',
        'shipping_address' => [
            'first_name' => 'Alex',
            'last_name' => 'Buyer',
            'address1' => '1 Test Street',
            'city' => 'Berlin',
            'country' => 'Nowhere',
            'country_code' => 'ZZ',
            'postal_code' => '10115',
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('shipping_address.country_code');
});
