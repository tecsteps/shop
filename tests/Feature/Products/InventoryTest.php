<?php

use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;

beforeEach(function () {
    $context = createStoreContext();
    $this->store = $context['store'];
    $this->service = app(InventoryService::class);
});

/**
 * Create a variant with an inventory item in a known state.
 *
 * @param  array{quantity_on_hand?: int, quantity_reserved?: int, policy?: string}  $inventory
 */
function makeInventoryItem(array $inventory = []): InventoryItem
{
    $product = Product::factory()->create(['store_id' => test()->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $item = $variant->inventoryItem;
    $item->fill(array_merge([
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
    ], $inventory));
    $item->save();

    return $item->refresh();
}

it('creates inventory item when variant is created', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $item = $variant->inventoryItem;

    expect($item)->not->toBeNull()
        ->and($item->quantity_on_hand)->toBe(0)
        ->and($item->quantity_reserved)->toBe(0);
});

it('checks availability correctly', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 10, 'quantity_reserved' => 3]);

    expect($item->available())->toBe(7);
});

it('reserves inventory', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 10, 'quantity_reserved' => 0]);

    $this->service->reserve($item, 3);

    $item->refresh();

    expect($item->quantity_reserved)->toBe(3)
        ->and($item->available())->toBe(7);
});

it('throws InsufficientInventoryException when reserving more than available with deny policy', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 5, 'quantity_reserved' => 3, 'policy' => 'deny']);

    expect(fn () => $this->service->reserve($item, 3))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows overselling with continue policy', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 2, 'quantity_reserved' => 0, 'policy' => 'continue']);

    $this->service->reserve($item, 5);

    expect($item->refresh()->quantity_reserved)->toBe(5);
});

it('releases reserved inventory', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 10, 'quantity_reserved' => 5]);

    $this->service->release($item, 3);

    expect($item->refresh()->quantity_reserved)->toBe(2);
});

it('commits inventory on order completion', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 10, 'quantity_reserved' => 3]);

    $this->service->commit($item, 3);

    $item->refresh();

    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 5]);

    $this->service->restock($item, 10);

    expect($item->refresh()->quantity_on_hand)->toBe(15);
});
