<?php

use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use App\Services\ProductService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->inventoryService = app(InventoryService::class);
});

it('creates inventory item when variant is created', function () {
    $productService = app(ProductService::class);
    $product = $productService->create($this->store, ['title' => 'Test Product']);

    $variant = $product->variants()->first();
    $inventoryItem = $variant->inventoryItem;

    expect($inventoryItem)->not->toBeNull()
        ->and($inventoryItem->quantity_on_hand)->toBe(0)
        ->and($inventoryItem->quantity_reserved)->toBe(0);
});

it('checks availability correctly', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => 'deny',
    ]);

    expect($this->inventoryService->checkAvailability($item, 7))->toBeTrue()
        ->and($this->inventoryService->checkAvailability($item, 8))->toBeFalse();
});

it('reserves inventory', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $this->inventoryService->reserve($item, 3);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(3)
        ->and($item->quantity_on_hand)->toBe(10);
});

it('throws InsufficientInventoryException when reserving more than available with deny policy', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 3,
        'policy' => 'deny',
    ]);

    expect(fn () => $this->inventoryService->reserve($item, 3))
        ->toThrow(InsufficientInventoryException::class);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(3);
});

it('allows overselling with continue policy', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => 'continue',
    ]);

    $this->inventoryService->reserve($item, 5);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(5);
});

it('releases reserved inventory', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 5,
        'policy' => 'deny',
    ]);

    $this->inventoryService->release($item, 3);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(2)
        ->and($item->quantity_on_hand)->toBe(10);
});

it('commits inventory on order completion', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => 'deny',
    ]);

    $this->inventoryService->commit($item, 3);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 2,
        'policy' => 'deny',
    ]);

    $this->inventoryService->restock($item, 10);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(15)
        ->and($item->quantity_reserved)->toBe(2);
});
