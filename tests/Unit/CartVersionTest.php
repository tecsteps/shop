<?php

use App\Exceptions\CartVersionMismatchException;
use App\Models\Product;
use App\Services\CartService;
use App\Services\ProductService;

it('starts at version 1', function () {
    $ctx = createStoreContext();
    $cart = app(CartService::class)->create($ctx['store']);

    expect($cart->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'P', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);

    $cart = app(CartService::class)->create($ctx['store']);
    app(CartService::class)->addLine($cart, $product->variants()->first()->id, 1);

    expect($cart->fresh()->cart_version)->toBe(2);
});

it('increments version on update and remove', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'P', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);

    $cart = app(CartService::class)->create($ctx['store']);
    $line = app(CartService::class)->addLine($cart, $product->variants()->first()->id, 1);
    app(CartService::class)->updateLineQuantity($cart, $line->id, 3);
    expect($cart->fresh()->cart_version)->toBe(3);

    app(CartService::class)->removeLine($cart, $line->id);
    expect($cart->fresh()->cart_version)->toBe(4);
});

it('detects version mismatch', function () {
    $ctx = createStoreContext();
    $cart = app(CartService::class)->create($ctx['store']);
    $cart->increment('cart_version');
    $cart->increment('cart_version');

    expect(fn () => app(CartService::class)->assertVersion($cart->fresh(), 2))
        ->toThrow(CartVersionMismatchException::class);
});
