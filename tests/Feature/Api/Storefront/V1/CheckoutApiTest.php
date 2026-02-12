<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
    ]);
});

test('can create a checkout from a cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
    ]);

    $response = $this->postJson(
        'http://shop.test/api/storefront/v1/checkouts',
        ['cart_id' => $cart->id, 'email' => 'test@example.com'],
    );

    $response->assertCreated()
        ->assertJsonStructure([
            'id', 'store_id', 'cart_id', 'status', 'email', 'lines', 'totals',
        ])
        ->assertJsonPath('status', 'started')
        ->assertJsonPath('email', 'test@example.com');
});

test('creating checkout with empty cart fails', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $response = $this->postJson(
        'http://shop.test/api/storefront/v1/checkouts',
        ['cart_id' => $cart->id, 'email' => 'test@example.com'],
    );

    $response->assertUnprocessable();
});

test('creating checkout validates required fields', function () {
    $response = $this->postJson(
        'http://shop.test/api/storefront/v1/checkouts',
        [],
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['cart_id', 'email']);
});

test('can set address on a checkout', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
    ]);
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => 'started',
        'email' => 'test@example.com',
    ]);

    $response = $this->putJson(
        "http://shop.test/api/storefront/v1/checkouts/{$checkout->id}/address",
        [
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'Berlin',
                'country' => 'Germany',
                'country_code' => 'DE',
                'postal_code' => '10115',
            ],
            'use_shipping_as_billing' => true,
        ],
    );

    $response->assertOk()
        ->assertJsonPath('status', 'addressed')
        ->assertJsonPath('shipping_address_json.first_name', 'Jane')
        ->assertJsonPath('billing_address_json.first_name', 'Jane');
});

test('set address validates required fields', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'status' => 'started',
    ]);

    $response = $this->putJson(
        "http://shop.test/api/storefront/v1/checkouts/{$checkout->id}/address",
        ['shipping_address' => []],
    );

    $response->assertUnprocessable();
});
