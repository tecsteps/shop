<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeInventoryItem(int $onHand = 5, InventoryPolicy $policy = InventoryPolicy::Deny): InventoryItem
{
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    return InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $onHand,
        'quantity_reserved' => 0,
        'policy' => $policy,
    ]);
}

it('reserves inventory when stock is available', function () {
    $item = makeInventoryItem(5);
    $service = app(InventoryService::class);

    $service->reserve($item, 2);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(2)
        ->and($item->availableQuantity())->toBe(3);
});

it('rejects reservation when deny policy has insufficient stock', function () {
    $item = makeInventoryItem(1);
    $service = app(InventoryService::class);

    $service->reserve($item, 2);
})->throws(InsufficientInventoryException::class);

it('allows reservation below zero when policy is continue', function () {
    $item = makeInventoryItem(0, InventoryPolicy::Continue);
    $service = app(InventoryService::class);

    $service->reserve($item, 3);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(3);
});

it('commits reserved inventory', function () {
    $item = makeInventoryItem(10);
    $service = app(InventoryService::class);

    $service->reserve($item, 4);
    $service->commit($item->fresh(), 4);
    $item->refresh();

    expect($item->quantity_on_hand)->toBe(6)
        ->and($item->quantity_reserved)->toBe(0);
});
