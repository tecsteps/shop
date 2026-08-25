<?php

use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\InventoryService;

it('checks availability correctly', function () {
    $item = InventoryItem::factory()->create(['quantity_on_hand' => 10, 'quantity_reserved' => 3]);

    expect(app(InventoryService::class)->checkAvailability($item, 7))->toBeTrue();
    expect(app(InventoryService::class)->checkAvailability($item, 8))->toBeFalse();
});

it('reserves inventory', function () {
    $item = InventoryItem::factory()->create(['quantity_on_hand' => 10, 'quantity_reserved' => 0]);

    app(InventoryService::class)->reserve($item, 3);

    expect($item->fresh()->quantity_reserved)->toBe(3);
});

it('throws InsufficientInventoryException when reserving more than available with deny policy', function () {
    $item = InventoryItem::factory()->create(['quantity_on_hand' => 5, 'quantity_reserved' => 3, 'policy' => 'deny']);

    expect(fn () => app(InventoryService::class)->reserve($item, 3))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows overselling with continue policy', function () {
    $item = InventoryItem::factory()->create(['quantity_on_hand' => 2, 'quantity_reserved' => 0, 'policy' => 'continue']);

    app(InventoryService::class)->reserve($item, 5);

    expect($item->fresh()->quantity_reserved)->toBe(5);
});

it('releases reserved inventory', function () {
    $item = InventoryItem::factory()->create(['quantity_on_hand' => 10, 'quantity_reserved' => 5]);

    app(InventoryService::class)->release($item, 3);

    expect($item->fresh()->quantity_reserved)->toBe(2);
});

it('commits inventory on order completion', function () {
    $item = InventoryItem::factory()->create(['quantity_on_hand' => 10, 'quantity_reserved' => 3]);

    app(InventoryService::class)->commit($item, 3);

    expect($item->fresh()->quantity_on_hand)->toBe(7);
    expect($item->fresh()->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    $item = InventoryItem::factory()->create(['quantity_on_hand' => 5]);

    app(InventoryService::class)->restock($item, 10);

    expect($item->fresh()->quantity_on_hand)->toBe(15);
});
