<?php

use App\Exceptions\CartVersionMismatchException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Create a store with a purchasable variant and a cart.
 *
 * @return array{0: Store, 1: ProductVariant, 2: App\Models\Cart}
 */
function cartVersionSetup(): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(100)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    $cart = app(CartService::class)->create($store);

    return [$store, $variant, $cart];
}

test('starts at version 1', function () {
    [, , $cart] = cartVersionSetup();

    expect($cart->cart_version)->toBe(1);
});

test('increments version on add line', function () {
    [, $variant, $cart] = cartVersionSetup();

    app(CartService::class)->addLine($cart, $variant->id, 1);

    expect($cart->refresh()->cart_version)->toBe(2);
});

test('increments version on update quantity', function () {
    [, $variant, $cart] = cartVersionSetup();
    $service = app(CartService::class);
    $line = $service->addLine($cart, $variant->id, 1);

    $service->updateLineQuantity($cart->refresh(), $line->id, 3);

    expect($cart->refresh()->cart_version)->toBe(3);
});

test('increments version on remove line', function () {
    [, $variant, $cart] = cartVersionSetup();
    $service = app(CartService::class);
    $line = $service->addLine($cart, $variant->id, 1);

    $service->removeLine($cart->refresh(), $line->id);

    expect($cart->refresh()->cart_version)->toBe(3);
});

test('detects version mismatch', function () {
    [, $variant, $cart] = cartVersionSetup();
    $service = app(CartService::class);
    $service->addLine($cart, $variant->id, 1);
    $service->addLine($cart->refresh(), $variant->id, 1); // version 3

    $service->assertVersion($cart->refresh(), 2);
})->throws(CartVersionMismatchException::class);
