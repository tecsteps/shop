<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Services\InventoryService;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->service = app(InventoryService::class);
});

function makeInventory(App\Models\Store $store, array $attributes = []): InventoryItem
{
    $variant = ProductVariant::factory()->for(
        App\Models\Product::factory()->create(['store_id' => $store->id])
    )->create();

    return InventoryItem::factory()->create(array_merge([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
    ], $attributes));
}

test('available is on hand minus reserved', function () {
    $item = makeInventory($this->store, ['quantity_on_hand' => 10, 'quantity_reserved' => 3]);

    expect($item->available())->toBe(7);
});

test('reserve increments the reserved quantity', function () {
    $item = makeInventory($this->store);

    $this->service->reserve($item, 4);

    $item->refresh();

    expect($item->quantity_reserved)->toBe(4)
        ->and($item->quantity_on_hand)->toBe(10)
        ->and($item->available())->toBe(6);
});

test('reserve with deny policy throws when overselling', function () {
    $item = makeInventory($this->store, ['quantity_on_hand' => 2, 'policy' => InventoryPolicy::Deny]);

    $this->service->reserve($item, 3);
})->throws(InsufficientInventoryException::class);

test('reserve with continue policy allows overselling', function () {
    $item = makeInventory($this->store, ['quantity_on_hand' => 2, 'policy' => InventoryPolicy::Continue]);

    $this->service->reserve($item, 5);

    expect($item->refresh()->quantity_reserved)->toBe(5)
        ->and($item->available())->toBe(-3);
});

test('checkAvailability honors the policy', function () {
    $denyItem = makeInventory($this->store, ['quantity_on_hand' => 2, 'policy' => InventoryPolicy::Deny]);
    $continueItem = makeInventory($this->store, ['quantity_on_hand' => 0, 'policy' => InventoryPolicy::Continue]);

    expect($this->service->checkAvailability($denyItem, 2))->toBeTrue()
        ->and($this->service->checkAvailability($denyItem, 3))->toBeFalse()
        ->and($this->service->checkAvailability($continueItem, 100))->toBeTrue();
});

test('release decrements the reserved quantity', function () {
    $item = makeInventory($this->store, ['quantity_on_hand' => 10, 'quantity_reserved' => 6]);

    $this->service->release($item, 4);

    expect($item->refresh()->quantity_reserved)->toBe(2)
        ->and($item->quantity_on_hand)->toBe(10);
});

test('commit decrements both on hand and reserved', function () {
    $item = makeInventory($this->store, ['quantity_on_hand' => 10, 'quantity_reserved' => 5]);

    $this->service->commit($item, 3);

    $item->refresh();

    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(2);
});

test('restock increments the on hand quantity', function () {
    $item = makeInventory($this->store, ['quantity_on_hand' => 4, 'quantity_reserved' => 2]);

    $this->service->restock($item, 6);

    $item->refresh();

    expect($item->quantity_on_hand)->toBe(10)
        ->and($item->quantity_reserved)->toBe(2);
});

test('the full stock lifecycle arithmetic works out', function () {
    $item = makeInventory($this->store, ['quantity_on_hand' => 10]);

    $this->service->reserve($item, 3);
    $this->service->reserve($item->refresh(), 2);
    $this->service->commit($item->refresh(), 3);
    $this->service->release($item->refresh(), 2);
    $this->service->restock($item->refresh(), 1);

    $item->refresh();

    expect($item->quantity_on_hand)->toBe(8)
        ->and($item->quantity_reserved)->toBe(0)
        ->and($item->available())->toBe(8);
});

test('variant helpers expose stock state', function () {
    $item = makeInventory($this->store, ['quantity_on_hand' => 5, 'quantity_reserved' => 2]);

    $variant = $item->variant->refresh();

    expect($variant->availableQuantity())->toBe(3)
        ->and($variant->isInStock())->toBeTrue()
        ->and($variant->isBackorderable())->toBeFalse();

    $backorderable = makeInventory($this->store, ['quantity_on_hand' => 0, 'policy' => InventoryPolicy::Continue]);

    expect($backorderable->variant->refresh()->isBackorderable())->toBeTrue()
        ->and($backorderable->variant->isInStock())->toBeFalse();
});
