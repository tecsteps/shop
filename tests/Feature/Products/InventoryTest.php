<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Services\InventoryService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = new InventoryService;
});

it('creates inventory with variant', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 5,
    ]);

    expect($inventory->quantity_on_hand)->toBe(50)
        ->and($inventory->quantity_reserved)->toBe(5)
        ->and($inventory->available())->toBe(45)
        ->and($inventory->variant_id)->toBe($variant->id);
});

it('checks availability', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
    ]);

    expect($this->service->checkAvailability($inventory, 5))->toBeTrue()
        ->and($this->service->checkAvailability($inventory, 7))->toBeTrue()
        ->and($this->service->checkAvailability($inventory, 8))->toBeFalse();
});

it('reserves inventory', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 20,
        'quantity_reserved' => 0,
    ]);

    $this->service->reserve($inventory, 5);

    $inventory->refresh();
    expect($inventory->quantity_reserved)->toBe(5)
        ->and($inventory->available())->toBe(15);
});

it('throws on insufficient inventory with deny policy', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->service->reserve($inventory, 10);
})->throws(InsufficientInventoryException::class);

it('allows overselling with continue policy', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    $this->service->reserve($inventory, 10);

    $inventory->refresh();
    expect($inventory->quantity_reserved)->toBe(10);
});

it('releases reserved inventory', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 20,
        'quantity_reserved' => 5,
    ]);

    $this->service->release($inventory, 3);

    $inventory->refresh();
    expect($inventory->quantity_reserved)->toBe(2)
        ->and($inventory->available())->toBe(18);
});

it('commits inventory', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 20,
        'quantity_reserved' => 5,
    ]);

    $this->service->commit($inventory, 5);

    $inventory->refresh();
    expect($inventory->quantity_on_hand)->toBe(15)
        ->and($inventory->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => \App\Models\Product::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
    ]);

    $this->service->restock($inventory, 25);

    $inventory->refresh();
    expect($inventory->quantity_on_hand)->toBe(35);
});
