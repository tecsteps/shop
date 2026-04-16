<?php

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\InsufficientInventoryException;
use App\Services\Inventory\InventoryService;

beforeEach(function () {
    $this->store = bindCurrentStore(makeStore());

    $this->product = Product::create([
        'store_id' => $this->store->id,
        'title' => 'P',
        'handle' => 'p-'.uniqid(),
        'status' => 'active',
        'description_html' => '',
        'tags' => [],
        'published_at' => now(),
    ]);

    $this->variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'price_amount' => 1000,
        'currency' => 'EUR',
        'requires_shipping' => true,
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);

    $this->inventory = InventoryItem::create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);
});

it('reserves inventory and decreases available', function () {
    app(InventoryService::class)->reserve($this->inventory, 3);

    expect($this->inventory->fresh()->quantity_reserved)->toBe(3)
        ->and($this->inventory->fresh()->available())->toBe(7);
});

it('throws when deny policy and insufficient stock', function () {
    app(InventoryService::class)->reserve($this->inventory, 3);

    app(InventoryService::class)->reserve($this->inventory, 10);
})->throws(InsufficientInventoryException::class);

it('commits sale by decrementing on-hand and reserved', function () {
    $service = app(InventoryService::class);
    $service->reserve($this->inventory, 3);
    $service->commit($this->inventory, 3);

    expect($this->inventory->fresh()->quantity_on_hand)->toBe(7)
        ->and($this->inventory->fresh()->quantity_reserved)->toBe(0);
});

it('restocks', function () {
    app(InventoryService::class)->restock($this->inventory, 5);

    expect($this->inventory->fresh()->quantity_on_hand)->toBe(15);
});
