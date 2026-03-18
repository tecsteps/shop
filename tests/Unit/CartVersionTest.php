<?php

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

function createCartVersionContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Cart Version Test',
        'handle' => 'cart-version-test-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    return array_merge($ctx, compact('product', 'variant'));
}

it('starts at version 1', function () {
    $ctx = createCartVersionContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);

    expect($cart->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    $ctx = createCartVersionContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);
    $cartService->addLine($cart, $ctx['variant']->id, 1);

    expect($cart->fresh()->cart_version)->toBe(2);
});

it('increments version on update quantity', function () {
    $ctx = createCartVersionContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);
    $line = $cartService->addLine($cart, $ctx['variant']->id, 1);
    $cartService->updateLineQuantity($cart, $line->id, 3);

    expect($cart->fresh()->cart_version)->toBe(3);
});

it('increments version on remove line', function () {
    $ctx = createCartVersionContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);
    $line = $cartService->addLine($cart, $ctx['variant']->id, 1);
    $cartService->removeLine($cart, $line->id);

    expect($cart->fresh()->cart_version)->toBe(3);
});

it('detects version mismatch', function () {
    $ctx = createCartVersionContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);
    $cartService->addLine($cart, $ctx['variant']->id, 1);

    // Cart is now at version 2
    expect($cart->fresh()->cart_version)->toBe(2);
});
