<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->inventoryService = new InventoryService;
});

it('creates inventory item for a variant', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $inventory = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
    ]);

    expect($inventory->exists)->toBeTrue()
        ->and($inventory->quantity_on_hand)->toBe(0)
        ->and($inventory->quantity_reserved)->toBe(0);
});

it('checks availability correctly', function () {
    $inventory = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    expect($inventory->quantityAvailable())->toBe(7)
        ->and($this->inventoryService->checkAvailability($inventory, 7))->toBeTrue()
        ->and($this->inventoryService->checkAvailability($inventory, 8))->toBeFalse();
});

it('reserves inventory', function () {
    $inventory = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->reserve($inventory, 3);

    expect($inventory->fresh()->quantity_reserved)->toBe(3)
        ->and($inventory->fresh()->quantityAvailable())->toBe(7);
});

it('throws InsufficientInventoryException when reserving more than available with deny policy', function () {
    $inventory = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->reserve($inventory, 3);
})->throws(InsufficientInventoryException::class);

it('allows overselling with continue policy', function () {
    $inventory = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    $this->inventoryService->reserve($inventory, 5);

    expect($inventory->fresh()->quantity_reserved)->toBe(5);
});

it('releases reserved inventory', function () {
    $inventory = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 5,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->release($inventory, 3);

    expect($inventory->fresh()->quantity_reserved)->toBe(2);
});

it('commits inventory on order completion', function () {
    $inventory = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->commit($inventory, 3);

    expect($inventory->fresh()->quantity_on_hand)->toBe(7)
        ->and($inventory->fresh()->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    $inventory = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->inventoryService->restock($inventory, 10);

    expect($inventory->fresh()->quantity_on_hand)->toBe(15);
});
