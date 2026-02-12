<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Store;
use App\Services\InventoryService;

it('denies reservations when policy is deny and stock is insufficient', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant(
        $store,
        inventoryAttributes: [
            'policy' => InventoryPolicy::Deny,
            'quantity_on_hand' => 3,
            'quantity_reserved' => 2,
        ],
    );

    $service = app(InventoryService::class);

    expect(fn () => $service->assertCanReserve($variant, 2))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows reservations when policy is continue selling', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant(
        $store,
        inventoryAttributes: [
            'policy' => InventoryPolicy::ContinueSelling,
            'quantity_on_hand' => 1,
            'quantity_reserved' => 0,
        ],
    );

    $service = app(InventoryService::class);
    $service->assertCanReserve($variant, 10);
    $updated = $service->reserve($variant, 10);

    expect($updated->quantity_reserved)->toBe(10);
});
