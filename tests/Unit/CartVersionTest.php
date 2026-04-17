<?php

use App\Exceptions\CartVersionMismatchException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->cartService = new CartService;
});

it('starts at version 1', function () {
    $cart = $this->cartService->create($this->store);

    expect($cart->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $this->cartService->addLine($cart, $variant->id, 1);

    expect($cart->fresh()->cart_version)->toBe(2);
});

it('increments version on update quantity', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $line = $this->cartService->addLine($cart, $variant->id, 1);
    $versionAfterAdd = $cart->fresh()->cart_version;

    $this->cartService->updateLineQuantity($cart, $line->id, 5);

    expect($cart->fresh()->cart_version)->toBe($versionAfterAdd + 1);
});

it('increments version on remove line', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $line = $this->cartService->addLine($cart, $variant->id, 1);
    $versionAfterAdd = $cart->fresh()->cart_version;

    $this->cartService->removeLine($cart, $line->id);

    expect($cart->fresh()->cart_version)->toBe($versionAfterAdd + 1);
});

it('detects version mismatch', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    // Add lines to push version to 3
    $this->cartService->addLine($cart, $variant->id, 1);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 3000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant2->id, 'quantity_on_hand' => 10]);
    $this->cartService->addLine($cart, $variant2->id, 1);

    expect($cart->fresh()->cart_version)->toBe(3);

    // Try with wrong version
    $this->cartService->addLine($cart, $variant->id, 1, expectedVersion: 2);
})->throws(CartVersionMismatchException::class);
