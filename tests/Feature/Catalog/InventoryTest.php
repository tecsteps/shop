<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\InventoryService;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->service = new InventoryService;
});

function createInventoryItem(Store $store, array $overrides = []): InventoryItem
{
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $store->id])->id,
    ]);

    return InventoryItem::create(array_merge([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ], $overrides));
}

it('checks availability returns true when stock is sufficient', function () {
    $item = createInventoryItem($this->store, ['quantity_on_hand' => 10]);

    expect($this->service->checkAvailability($item, 5))->toBeTrue();
});

it('checks availability returns false when stock is insufficient with deny policy', function () {
    $item = createInventoryItem($this->store, ['quantity_on_hand' => 3]);

    expect($this->service->checkAvailability($item, 5))->toBeFalse();
});

it('checks availability returns true with continue policy regardless of stock', function () {
    $item = createInventoryItem($this->store, [
        'quantity_on_hand' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    expect($this->service->checkAvailability($item, 5))->toBeTrue();
});

it('reserves stock successfully', function () {
    $item = createInventoryItem($this->store, ['quantity_on_hand' => 10]);

    $this->service->reserve($item, 3);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(3)
        ->and($item->quantity_on_hand)->toBe(10)
        ->and($item->available)->toBe(7);
});

it('throws exception when reserving more than available with deny policy', function () {
    $item = createInventoryItem($this->store, ['quantity_on_hand' => 2]);

    $this->service->reserve($item, 5);
})->throws(InsufficientInventoryException::class);

it('allows reserving beyond stock with continue policy', function () {
    $item = createInventoryItem($this->store, [
        'quantity_on_hand' => 2,
        'policy' => InventoryPolicy::Continue,
    ]);

    $this->service->reserve($item, 5);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(5);
});

it('releases reserved stock', function () {
    $item = createInventoryItem($this->store, [
        'quantity_on_hand' => 10,
        'quantity_reserved' => 5,
    ]);

    $this->service->release($item, 3);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(2)
        ->and($item->available)->toBe(8);
});

it('commits stock reducing both on_hand and reserved', function () {
    $item = createInventoryItem($this->store, [
        'quantity_on_hand' => 10,
        'quantity_reserved' => 5,
    ]);

    $this->service->commit($item, 3);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(2);
});

it('restocks by incrementing on_hand', function () {
    $item = createInventoryItem($this->store, ['quantity_on_hand' => 5]);

    $this->service->restock($item, 10);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(15);
});

it('computes available quantity correctly', function () {
    $item = createInventoryItem($this->store, [
        'quantity_on_hand' => 20,
        'quantity_reserved' => 8,
    ]);

    expect($item->available)->toBe(12);
});

it('handles full lifecycle: reserve, commit, restock', function () {
    $item = createInventoryItem($this->store, ['quantity_on_hand' => 10]);

    $this->service->reserve($item, 3);
    $item->refresh();
    expect($item->available)->toBe(7);

    $this->service->commit($item, 3);
    $item->refresh();
    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0);

    $this->service->restock($item, 5);
    $item->refresh();
    expect($item->quantity_on_hand)->toBe(12);
});
