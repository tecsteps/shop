<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\ProductService;

beforeEach(function () {
    $ctx = createStoreContext();
    $this->store = $ctx['store'];
    $this->inventoryService = app(InventoryService::class);
});

test('creates inventory item when variant is created', function () {
    $productService = app(ProductService::class);
    $product = $productService->create($this->store, ['title' => 'Test Product']);

    $variant = $product->variants()->first();

    expect($variant->inventoryItem)->not->toBeNull()
        ->and($variant->inventoryItem->quantity_on_hand)->toBe(0)
        ->and($variant->inventoryItem->quantity_reserved)->toBe(0);
});

test('checks availability correctly', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    expect($this->inventoryService->checkAvailability($item, 7))->toBeTrue()
        ->and($this->inventoryService->checkAvailability($item, 8))->toBeFalse();
});

test('reserves inventory', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->reserve($item, 3);

    expect($item->fresh()->quantity_reserved)->toBe(3);
});

test('throws InsufficientInventoryException when reserving more than available with deny policy', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    expect(fn () => $this->inventoryService->reserve($item, 3))
        ->toThrow(InsufficientInventoryException::class);
});

test('allows overselling with continue policy', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    $this->inventoryService->reserve($item, 5);

    expect($item->fresh()->quantity_reserved)->toBe(5);
});

test('releases reserved inventory', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 5,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->release($item, 3);

    expect($item->fresh()->quantity_reserved)->toBe(2);
});

test('commits inventory on order completion', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->commit($item, 3);

    expect($item->fresh()->quantity_on_hand)->toBe(7)
        ->and($item->fresh()->quantity_reserved)->toBe(0);
});

test('restocks inventory', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->restock($item, 10);

    expect($item->fresh()->quantity_on_hand)->toBe(15);
});
