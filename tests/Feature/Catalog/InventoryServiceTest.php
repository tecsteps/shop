<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\InventoryService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeVariantWithInventory(int $onHand = 10, InventoryPolicy $policy = InventoryPolicy::Deny): ProductVariant
{
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->getKey()]);

    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => $onHand,
        'quantity_reserved' => 0,
        'policy' => $policy->value,
    ]);

    return $variant;
}

it('reserves inventory and bumps the reserved count', function () {
    $variant = makeVariantWithInventory(10);

    $item = app(InventoryService::class)->reserve($variant, 3);

    expect($item->quantity_on_hand)->toBe(10)
        ->and($item->quantity_reserved)->toBe(3);
});

it('throws when deny policy is short on stock', function () {
    $variant = makeVariantWithInventory(2);

    app(InventoryService::class)->reserve($variant, 5);
})->throws(InsufficientInventoryException::class);

it('allows reserving negative available inventory under continue policy', function () {
    $variant = makeVariantWithInventory(1, InventoryPolicy::Continue);

    $item = app(InventoryService::class)->reserve($variant, 5);

    expect($item->quantity_reserved)->toBe(5)
        ->and($item->available())->toBe(-4);
});

it('releases reserved inventory without going below zero', function () {
    $variant = makeVariantWithInventory(10);
    $service = app(InventoryService::class);

    $service->reserve($variant, 4);
    $item = $service->release($variant, 2);

    expect($item->quantity_reserved)->toBe(2);

    $item = $service->release($variant, 100);
    expect($item->quantity_reserved)->toBe(0);
});

it('commits reserved inventory and decrements on hand', function () {
    $variant = makeVariantWithInventory(10);
    $service = app(InventoryService::class);

    $service->reserve($variant, 3);
    $item = $service->commit($variant, 3);

    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0);
});

it('restocks on hand on refund', function () {
    $variant = makeVariantWithInventory(5);

    $item = app(InventoryService::class)->restock($variant, 4);

    expect($item->quantity_on_hand)->toBe(9);
});

it('reports availability for deny and continue policies', function () {
    $deny = makeVariantWithInventory(2);
    $continue = makeVariantWithInventory(0, InventoryPolicy::Continue);
    $service = app(InventoryService::class);

    expect($service->checkAvailability($deny, 2))->toBeTrue()
        ->and($service->checkAvailability($deny, 3))->toBeFalse()
        ->and($service->checkAvailability($continue, 9999))->toBeTrue();
});
