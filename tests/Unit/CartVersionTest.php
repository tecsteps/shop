<?php

use App\Enums\CartStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Models\Cart;
use App\Services\CartService;
use Tests\Support\CommerceTestHelper;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('starts at version 1', function () {
    ['store' => $store] = test()->createStoreContext();

    $cart = app(CartService::class)->create($store);

    expect($cart->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    ['store' => $store] = test()->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    expect($cart->refresh()->cart_version)->toBe(2);
});

it('increments version on update quantity', function () {
    ['store' => $store] = test()->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(CartService::class)->create($store);
    $line = app(CartService::class)->addLine($cart, $variant->id, 1);
    app(CartService::class)->updateLineQuantity($cart->refresh(), $line->id, 3);

    expect($cart->refresh()->cart_version)->toBe(3);
});

it('increments version on remove line', function () {
    ['store' => $store] = test()->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(CartService::class)->create($store);
    $line = app(CartService::class)->addLine($cart, $variant->id, 1);
    app(CartService::class)->removeLine($cart->refresh(), $line->id);

    expect($cart->refresh()->cart_version)->toBe(3);
    expect(Cart::query()->whereKey($cart->id)->first()->status)->toBe(CartStatus::Active);
});

it('detects version mismatch', function () {
    ['store' => $store] = test()->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);
    app(CartService::class)->addLine($cart->refresh(), $variant->id, 1);

    expect($cart->refresh()->cart_version)->toBe(3);

    app(CartService::class)->addLine($cart->refresh(), $variant->id, 1, 2);
})->throws(CartVersionMismatchException::class);
