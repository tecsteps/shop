<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $product = Product::factory()->for($this->store)->create();
    $this->variant = ProductVariant::factory()->for($product)->create();
    $this->inventory = $this->variant->inventoryItem;
    $this->inventoryService = app(InventoryService::class);
});

it('automatically creates inventory for every variant', function () {
    expect($this->inventory)->not->toBeNull()
        ->and($this->inventory->store_id)->toBe($this->store->id)
        ->and($this->inventory->quantity_on_hand)->toBe(0)
        ->and($this->inventory->quantity_reserved)->toBe(0)
        ->and($this->inventory->policy)->toBe(InventoryPolicy::Deny);
});

it('reserves releases commits and restocks inventory', function () {
    $this->inventory->update(['quantity_on_hand' => 10]);

    $this->inventoryService->reserve($this->inventory, 3);
    expect($this->inventory->quantity_reserved)->toBe(3)->and($this->inventory->available)->toBe(7);

    $this->inventoryService->release($this->inventory, 1);
    expect($this->inventory->quantity_reserved)->toBe(2);

    $this->inventoryService->commit($this->inventory, 2);
    expect($this->inventory->quantity_on_hand)->toBe(8)->and($this->inventory->quantity_reserved)->toBe(0);

    $this->inventoryService->restock($this->inventory, 4);
    expect($this->inventory->quantity_on_hand)->toBe(12);
});

it('rejects over reservation under deny policy', function () {
    $this->inventory->update(['quantity_on_hand' => 5, 'quantity_reserved' => 3]);

    expect(fn () => $this->inventoryService->reserve($this->inventory, 3))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows over reservation under continue policy', function () {
    $this->inventory->update([
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    $this->inventoryService->reserve($this->inventory, 5);

    expect($this->inventory->quantity_reserved)->toBe(5)
        ->and($this->inventory->available)->toBe(-3);
});
