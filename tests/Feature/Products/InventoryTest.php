<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Services\InventoryService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->service = app(InventoryService::class);
});

it('checks availability correctly', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    expect($item->quantity_available)->toBe(7)
        ->and($this->service->checkAvailability($item, 7))->toBeTrue()
        ->and($this->service->checkAvailability($item, 8))->toBeFalse();
});

it('reserves inventory', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->service->reserve($item, 3);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(3)
        ->and($item->quantity_available)->toBe(7);
});

it('throws InsufficientInventoryException when reserving more than available with deny policy', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->service->reserve($item, 3);
})->throws(InsufficientInventoryException::class);

it('allows overselling with continue policy', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    $this->service->reserve($item, 5);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(5);
});

it('releases reserved inventory', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 5,
    ]);

    $this->service->release($item, 3);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(2);
});

it('commits inventory on order completion', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
    ]);

    $this->service->commit($item, 3);
    $item->refresh();

    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
    ]);

    $this->service->restock($item, 10);
    $item->refresh();

    expect($item->quantity_on_hand)->toBe(15);
});
