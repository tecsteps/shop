<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(InventoryService::class);
});

function createInventoryItem(Store $store, int $onHand = 10, int $reserved = 0, InventoryPolicy $policy = InventoryPolicy::Deny): InventoryItem
{
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $store->id])->id,
    ]);

    return InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $onHand,
        'quantity_reserved' => $reserved,
        'policy' => $policy,
    ]);
}

test('checkAvailability returns true when sufficient stock with deny policy', function () {
    $item = createInventoryItem($this->store, onHand: 10, reserved: 3);

    expect($this->service->checkAvailability($item, 5))->toBeTrue();
});

test('checkAvailability returns false when insufficient stock with deny policy', function () {
    $item = createInventoryItem($this->store, onHand: 10, reserved: 8);

    expect($this->service->checkAvailability($item, 5))->toBeFalse();
});

test('checkAvailability always returns true with continue policy', function () {
    $item = createInventoryItem($this->store, onHand: 0, reserved: 0, policy: InventoryPolicy::Continue);

    expect($this->service->checkAvailability($item, 100))->toBeTrue();
});

test('reserve increments quantity_reserved', function () {
    $item = createInventoryItem($this->store, onHand: 10);

    $this->service->reserve($item, 3);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(10);
    expect($item->quantity_reserved)->toBe(3);
});

test('reserve throws when deny policy and insufficient available stock', function () {
    $item = createInventoryItem($this->store, onHand: 5, reserved: 3);

    expect(fn () => $this->service->reserve($item, 5))
        ->toThrow(InsufficientInventoryException::class);
});

test('reserve succeeds with continue policy even when out of stock', function () {
    $item = createInventoryItem($this->store, onHand: 0, reserved: 0, policy: InventoryPolicy::Continue);

    $this->service->reserve($item, 5);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(5);
});

test('release decrements quantity_reserved', function () {
    $item = createInventoryItem($this->store, onHand: 10, reserved: 5);

    $this->service->release($item, 3);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(10);
    expect($item->quantity_reserved)->toBe(2);
});

test('commit decrements both quantity_on_hand and quantity_reserved', function () {
    $item = createInventoryItem($this->store, onHand: 10, reserved: 5);

    $this->service->commit($item, 3);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(7);
    expect($item->quantity_reserved)->toBe(2);
});

test('restock increments quantity_on_hand', function () {
    $item = createInventoryItem($this->store, onHand: 5);

    $this->service->restock($item, 10);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(15);
});

test('available quantity is on_hand minus reserved', function () {
    $item = createInventoryItem($this->store, onHand: 20, reserved: 7);

    expect($item->availableQuantity())->toBe(13);
});
