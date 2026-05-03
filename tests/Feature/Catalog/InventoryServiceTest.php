<?php

use App\Exceptions\InsufficientInventoryException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inventory reserve release commit and restock update quantities transactionally', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::factory()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->getKey()]);
    $item = $variant->inventoryItem()->firstOrFail();
    $item->forceFill(['quantity_on_hand' => 10, 'quantity_reserved' => 0, 'policy' => 'deny'])->save();

    $service = app(InventoryService::class);

    $service->reserve($item, 4);
    expect($item->refresh()->quantity_reserved)->toBe(4);

    $service->release($item, 1);
    expect($item->refresh()->quantity_reserved)->toBe(3);

    $service->commit($item, 2);
    expect($item->refresh()->quantity_on_hand)->toBe(8)
        ->and($item->quantity_reserved)->toBe(1);

    $service->restock($item, 5);
    expect($item->refresh()->quantity_on_hand)->toBe(13);
});

test('deny policy blocks reservations above available stock while continue allows it', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $denyProduct = Product::factory()->create(['store_id' => $store->getKey()]);
    $denyVariant = ProductVariant::factory()->create(['product_id' => $denyProduct->getKey()]);
    $denyItem = $denyVariant->inventoryItem()->firstOrFail();
    $denyItem->forceFill(['quantity_on_hand' => 1, 'quantity_reserved' => 0, 'policy' => 'deny'])->save();

    expect(fn () => app(InventoryService::class)->reserve($denyItem, 2))
        ->toThrow(InsufficientInventoryException::class);

    $continueProduct = Product::factory()->create(['store_id' => $store->getKey()]);
    $continueVariant = ProductVariant::factory()->create(['product_id' => $continueProduct->getKey()]);
    $continueItem = $continueVariant->inventoryItem()->firstOrFail();
    $continueItem->forceFill(['quantity_on_hand' => 0, 'quantity_reserved' => 0, 'policy' => 'continue'])->save();

    app(InventoryService::class)->reserve($continueItem, 2);

    expect($continueItem->refresh()->quantity_reserved)->toBe(2);
});
