<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Services\InventoryService;
use App\Services\ProductService;

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
    $this->products = app(ProductService::class);
    $this->inventory = app(InventoryService::class);
});

it('creates an inventory item when a variant is created', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Tee']);

    $item = $product->variants->first()->inventoryItem;

    expect($item)->not->toBeNull();
    expect($item->quantity_on_hand)->toBe(0);
    expect($item->quantity_reserved)->toBe(0);
});

it('checks availability correctly', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Tee']);
    $item = $product->variants->first()->inventoryItem;
    $item->update(['quantity_on_hand' => 10, 'quantity_reserved' => 3]);

    expect($this->inventory->checkAvailability($item->fresh(), 7))->toBeTrue();
    expect($this->inventory->checkAvailability($item->fresh(), 8))->toBeFalse();
});

it('reserves inventory', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Tee']);
    $item = $product->variants->first()->inventoryItem;
    $item->update(['quantity_on_hand' => 10]);

    $this->inventory->reserve($item->fresh(), 3);

    $fresh = $item->fresh();
    expect($fresh->quantity_reserved)->toBe(3);
    expect($fresh->available())->toBe(7);
});

it('throws InsufficientInventoryException when reserving more than available with deny policy', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Tee']);
    $item = $product->variants->first()->inventoryItem;
    $item->update(['quantity_on_hand' => 5, 'quantity_reserved' => 3, 'policy' => InventoryPolicy::Deny]);

    expect(fn () => $this->inventory->reserve($item->fresh(), 3))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows overselling with continue policy', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Tee']);
    $item = $product->variants->first()->inventoryItem;
    $item->update(['quantity_on_hand' => 2, 'policy' => InventoryPolicy::Continue]);

    $this->inventory->reserve($item->fresh(), 5);

    expect($item->fresh()->quantity_reserved)->toBe(5);
});

it('releases reserved inventory', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Tee']);
    $item = $product->variants->first()->inventoryItem;
    $item->update(['quantity_on_hand' => 10, 'quantity_reserved' => 5]);

    $this->inventory->release($item->fresh(), 3);

    expect($item->fresh()->quantity_reserved)->toBe(2);
});

it('commits inventory on order completion', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Tee']);
    $item = $product->variants->first()->inventoryItem;
    $item->update(['quantity_on_hand' => 10, 'quantity_reserved' => 3]);

    $this->inventory->commit($item->fresh(), 3);

    $fresh = $item->fresh();
    expect($fresh->quantity_on_hand)->toBe(7);
    expect($fresh->quantity_reserved)->toBe(0);
});

it('restocks inventory', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Tee']);
    $item = $product->variants->first()->inventoryItem;
    $item->update(['quantity_on_hand' => 5]);

    $this->inventory->restock($item->fresh(), 10);

    expect($item->fresh()->quantity_on_hand)->toBe(15);
});
