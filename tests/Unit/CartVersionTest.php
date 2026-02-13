<?php

use App\Enums\VariantStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createCartVersionContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
        'quantity_reserved' => 0,
    ]);

    $cartService = app(CartService::class);
    $cart = $cartService->create($store);

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'cart' => $cart,
        'cartService' => $cartService,
    ]);
}

it('starts at version 1', function () {
    $ctx = createCartVersionContext();

    expect($ctx['cart']->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    $ctx = createCartVersionContext();
    $cart = $ctx['cart'];
    $variant = $ctx['variant'];
    $cartService = $ctx['cartService'];

    $cartService->addLine($cart, $variant->id, 1);

    expect($cart->fresh()->cart_version)->toBe(2);
});

it('increments version on update quantity', function () {
    $ctx = createCartVersionContext();
    $cart = $ctx['cart'];
    $variant = $ctx['variant'];
    $cartService = $ctx['cartService'];

    $line = $cartService->addLine($cart, $variant->id, 1);
    $cartService->updateLineQuantity($cart->fresh(), $line->id, 3);

    expect($cart->fresh()->cart_version)->toBe(3);
});

it('increments version on remove line', function () {
    $ctx = createCartVersionContext();
    $cart = $ctx['cart'];
    $variant = $ctx['variant'];
    $cartService = $ctx['cartService'];

    $line = $cartService->addLine($cart, $variant->id, 1);
    $cartService->removeLine($cart->fresh(), $line->id);

    expect($cart->fresh()->cart_version)->toBe(3);
});

it('detects version mismatch', function () {
    $ctx = createCartVersionContext();
    $cart = $ctx['cart'];
    $cartService = $ctx['cartService'];

    expect(fn () => $cartService->validateVersion($cart, 999))
        ->toThrow(CartVersionMismatchException::class);
});
