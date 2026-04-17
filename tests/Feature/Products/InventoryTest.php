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

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->inventory = new InventoryService;
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

function makeInventoryItem(Store $store, int $onHand = 10, InventoryPolicy $policy = InventoryPolicy::Deny): InventoryItem
{
    $product = Product::factory()->for($store)->create();
    $variant = ProductVariant::factory()->for($product)->create();

    return InventoryItem::factory()
        ->for($store)
        ->for($variant, 'variant')
        ->create([
            'quantity_on_hand' => $onHand,
            'policy' => $policy->value,
        ]);
}

it('reserves inventory when quantity is available', function (): void {
    $item = makeInventoryItem($this->store, 10);

    $this->inventory->reserve($item, 3);

    expect($item->fresh()->quantity_reserved)->toBe(3)
        ->and($item->fresh()->quantityAvailable())->toBe(7);
});

it('throws InsufficientInventoryException when policy is deny and insufficient', function (): void {
    $item = makeInventoryItem($this->store, 2, InventoryPolicy::Deny);

    $this->inventory->reserve($item, 5);
})->throws(InsufficientInventoryException::class);

it('allows over-selling when policy is continue', function (): void {
    $item = makeInventoryItem($this->store, 2, InventoryPolicy::Continue);

    $this->inventory->reserve($item, 5);

    expect($item->fresh()->quantity_reserved)->toBe(5)
        ->and($item->fresh()->quantityAvailable())->toBe(-3);
});

it('releases reserved inventory', function (): void {
    $item = makeInventoryItem($this->store, 10);
    $this->inventory->reserve($item, 4);

    $this->inventory->release($item->fresh(), 3);

    expect($item->fresh()->quantity_reserved)->toBe(1);
});

it('commits inventory on order completion', function (): void {
    $item = makeInventoryItem($this->store, 10);
    $this->inventory->reserve($item, 4);

    $this->inventory->commit($item->fresh(), 4);

    $fresh = $item->fresh();

    expect($fresh->quantity_on_hand)->toBe(6)
        ->and($fresh->quantity_reserved)->toBe(0);
});

it('restocks inventory', function (): void {
    $item = makeInventoryItem($this->store, 10);

    $this->inventory->restock($item, 5);

    expect($item->fresh()->quantity_on_hand)->toBe(15);
});
