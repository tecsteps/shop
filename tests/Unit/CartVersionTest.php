<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createCartVersionContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];
    $cartService = app(CartService::class);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    return compact('store', 'cartService', 'product', 'variant') + $ctx;
}

it('starts at version 1', function () {
    $ctx = createCartVersionContext();

    $cart = $ctx['cartService']->create($ctx['store']);

    expect($cart->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    $ctx = createCartVersionContext();
    $cart = $ctx['cartService']->create($ctx['store']);

    $ctx['cartService']->addLine($cart, $ctx['variant']->id, 1);

    expect($cart->fresh()->cart_version)->toBe(2);
});

it('increments version on update quantity', function () {
    $ctx = createCartVersionContext();
    $cart = $ctx['cartService']->create($ctx['store']);

    $line = $ctx['cartService']->addLine($cart, $ctx['variant']->id, 1);
    $versionAfterAdd = $cart->fresh()->cart_version;

    $ctx['cartService']->updateLineQuantity($cart->fresh(), $line->id, 3);

    expect($cart->fresh()->cart_version)->toBe($versionAfterAdd + 1);
});

it('increments version on remove line', function () {
    $ctx = createCartVersionContext();
    $cart = $ctx['cartService']->create($ctx['store']);

    $line = $ctx['cartService']->addLine($cart, $ctx['variant']->id, 1);
    $versionAfterAdd = $cart->fresh()->cart_version;

    $ctx['cartService']->removeLine($cart->fresh(), $line->id);

    expect($cart->fresh()->cart_version)->toBe($versionAfterAdd + 1);
});

it('detects version mismatch', function () {
    $ctx = createCartVersionContext();
    $cart = $ctx['cartService']->create($ctx['store']);

    $ctx['cartService']->addLine($cart, $ctx['variant']->id, 1);

    // Cart is now at version 2, client has version 1
    $currentVersion = $cart->fresh()->cart_version;

    expect($currentVersion)->toBe(2)
        ->and($currentVersion)->not->toBe(1);
});
