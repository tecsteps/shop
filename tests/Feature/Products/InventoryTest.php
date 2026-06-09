<?php

use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\ProductService;

function makeInventoryItem(array $attributes = []): InventoryItem
{
    $context = createStoreContext();

    $variant = ProductVariant::factory()
        ->for(\App\Models\Product::factory()->for($context['store']))
        ->create();

    return InventoryItem::factory()
        ->forVariant($variant)
        ->create($attributes);
}

it('creates inventory item when variant is created', function () {
    $context = createStoreContext();

    $product = app(ProductService::class)->create($context['store'], [
        'title' => 'Stocked Product',
    ]);

    $variant = $product->variants->first();

    $this->assertDatabaseHas('inventory_items', [
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
    ]);
});

it('checks availability correctly', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 10, 'quantity_reserved' => 3]);

    expect($item->availableQuantity())->toBe(7);
    expect(app(InventoryService::class)->checkAvailability($item, 7))->toBeTrue();
    expect(app(InventoryService::class)->checkAvailability($item, 8))->toBeFalse();
});

it('reserves inventory', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 10, 'quantity_reserved' => 0]);

    app(InventoryService::class)->reserve($item, 3);

    $item->refresh();

    expect($item->quantity_reserved)->toBe(3);
    expect($item->availableQuantity())->toBe(7);
});

it('throws InsufficientInventoryException when reserving more than available with deny policy', function () {
    $item = makeInventoryItem([
        'quantity_on_hand' => 5,
        'quantity_reserved' => 3,
        'policy' => 'deny',
    ]);

    expect(fn () => app(InventoryService::class)->reserve($item, 3))
        ->toThrow(InsufficientInventoryException::class);

    expect($item->refresh()->quantity_reserved)->toBe(3);
});

it('allows overselling with continue policy', function () {
    $item = makeInventoryItem([
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => 'continue',
    ]);

    app(InventoryService::class)->reserve($item, 5);

    expect($item->refresh()->quantity_reserved)->toBe(5);
});

it('releases reserved inventory', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 10, 'quantity_reserved' => 5]);

    app(InventoryService::class)->release($item, 3);

    expect($item->refresh()->quantity_reserved)->toBe(2);
});

it('commits inventory on order completion', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 10, 'quantity_reserved' => 3]);

    app(InventoryService::class)->commit($item, 3);

    $item->refresh();

    expect($item->quantity_on_hand)->toBe(7);
    expect($item->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    $item = makeInventoryItem(['quantity_on_hand' => 5]);

    app(InventoryService::class)->restock($item, 10);

    expect($item->refresh()->quantity_on_hand)->toBe(15);
});
