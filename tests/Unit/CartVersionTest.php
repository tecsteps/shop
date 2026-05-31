<?php

use App\Exceptions\CartVersionMismatchException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new CartService(new InventoryService);
    $this->store = Store::factory()->create();
});

/**
 * Create an active, in-stock variant for the store.
 */
function versionVariant(Store $store): ProductVariant
{
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    $variant->inventoryItem->update(['quantity_on_hand' => 100, 'policy' => 'continue']);

    return $variant;
}

it('starts at version 1', function () {
    $cart = $this->service->create($this->store);

    expect($cart->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    $cart = $this->service->create($this->store);
    $variant = versionVariant($this->store);

    $this->service->addLine($cart, $variant->id, 1);

    expect($cart->fresh()->cart_version)->toBe(2);
});

it('increments version on update quantity', function () {
    $cart = $this->service->create($this->store);
    $variant = versionVariant($this->store);
    $line = $this->service->addLine($cart, $variant->id, 1);

    $this->service->updateLineQuantity($cart->fresh(), $line->id, 3);

    expect($cart->fresh()->cart_version)->toBe(3);
});

it('increments version on remove line', function () {
    $cart = $this->service->create($this->store);
    $variant = versionVariant($this->store);
    $line = $this->service->addLine($cart, $variant->id, 1);

    $this->service->removeLine($cart->fresh(), $line->id);

    expect($cart->fresh()->cart_version)->toBe(3);
});

it('detects version mismatch', function () {
    $cart = $this->service->create($this->store);
    $variant = versionVariant($this->store);
    $this->service->addLine($cart, $variant->id, 1); // version now 2
    $this->service->addLine($cart->fresh(), $variant->id, 1); // version now 3

    expect(fn () => $this->service->addLine($cart->fresh(), $variant->id, 1, expectedVersion: 2))
        ->toThrow(CartVersionMismatchException::class);
});
