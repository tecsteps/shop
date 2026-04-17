<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->cartService = app(CartService::class);
});

it('starts cart at version 1', function () {
    $cart = $this->cartService->create($this->ctx['store']);

    expect($cart->cart_version)->toBe(1);
});

it('increments version on addLine', function () {
    $cart = $this->cartService->create($this->ctx['store']);
    $variant = createVersionTestVariant(2000, 10);

    $this->cartService->addLine($cart, $variant->id, 1);
    $cart->refresh();

    expect($cart->cart_version)->toBe(2);
});

it('increments version on updateLineQuantity', function () {
    $cart = $this->cartService->create($this->ctx['store']);
    $variant = createVersionTestVariant(2000, 10);
    $line = $this->cartService->addLine($cart, $variant->id, 1);
    $cart->refresh();

    $this->cartService->updateLineQuantity($cart, $line->id, 3);
    $cart->refresh();

    expect($cart->cart_version)->toBe(3);
});

it('increments version on removeLine', function () {
    $cart = $this->cartService->create($this->ctx['store']);
    $variant = createVersionTestVariant(2000, 10);
    $line = $this->cartService->addLine($cart, $variant->id, 1);
    $cart->refresh();

    $this->cartService->removeLine($cart, $line->id);
    $cart->refresh();

    expect($cart->cart_version)->toBe(3);
});

it('throws CartVersionMismatchException on stale version', function () {
    $cart = $this->cartService->create($this->ctx['store']);
    $variant = createVersionTestVariant(2000, 10);

    expect(fn () => $this->cartService->addLine($cart, $variant->id, 1, 99))
        ->toThrow(CartVersionMismatchException::class);
});

// --- Helper ---

function createVersionTestVariant(int $price, int $stock): ProductVariant
{
    $store = app('current_store');
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $price,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $stock,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    return $variant;
}
