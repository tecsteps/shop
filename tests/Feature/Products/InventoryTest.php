<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\ProductService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->inventoryService = app(InventoryService::class);
});

it('creates inventory item when variant is created', function () {
    $productService = app(ProductService::class);
    $product = $productService->create($this->ctx['store'], [
        'title' => 'Inventory Test Product',
        'price_amount' => 1000,
    ]);

    $variant = $product->variants()->first();
    $inventoryItem = $variant->inventoryItem;

    expect($inventoryItem)->not->toBeNull();
    expect($inventoryItem->quantity_on_hand)->toBe(0);
    expect($inventoryItem->quantity_reserved)->toBe(0);
});

it('checks availability correctly', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    expect($item->quantityAvailable())->toBe(7);
    expect($this->inventoryService->checkAvailability($item, 7))->toBeTrue();
    expect($this->inventoryService->checkAvailability($item, 8))->toBeFalse();
});

it('reserves inventory', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->reserve($item, 3);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(3);
    expect($item->quantityAvailable())->toBe(7);
});

it('throws InsufficientInventoryException with deny policy', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    expect(fn () => $this->inventoryService->reserve($item, 3))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows overselling with continue policy', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    $this->inventoryService->reserve($item, 5);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(5);
});

it('releases reserved inventory', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 5,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->release($item, 3);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(2);
});

it('commits inventory on order completion', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->commit($item, 3);
    $item->refresh();

    expect($item->quantity_on_hand)->toBe(7);
    expect($item->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->restock($item, 10);
    $item->refresh();

    expect($item->quantity_on_hand)->toBe(15);
});
