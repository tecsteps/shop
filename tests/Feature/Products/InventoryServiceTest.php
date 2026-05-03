<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Services\InventoryService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('inventory reserve release commit and restock update quantities transactionally', function () {
    $item = InventoryItem::factory()->create([
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);
    $service = app(InventoryService::class);

    $service->reserve($item, 4);
    expect($item->refresh()->quantity_reserved)->toBe(4)
        ->and($item->availableQuantity())->toBe(6);

    $service->release($item, 1);
    expect($item->refresh()->quantity_reserved)->toBe(3);

    $service->commit($item, 3);
    expect($item->refresh()->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0);

    $service->restock($item, 2);
    expect($item->refresh()->quantity_on_hand)->toBe(9);
});

test('deny policy blocks reservations above available inventory', function () {
    $item = InventoryItem::factory()->create([
        'quantity_on_hand' => 1,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    expect(fn () => app(InventoryService::class)->reserve($item, 2))
        ->toThrow(InsufficientInventoryException::class);
});

test('continue policy allows reservations above available inventory', function () {
    $item = InventoryItem::factory()->continuePolicy()->create([
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
    ]);

    app(InventoryService::class)->reserve($item, 2);

    expect($item->refresh()->quantity_reserved)->toBe(2);
});
