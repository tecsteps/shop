<?php

use App\Enums\InventoryPolicy;
use App\Livewire\Admin\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

/**
 * Create a product with one variant and an inventory item.
 */
function makeInventoryItem(array $productAttributes, array $variantAttributes, int $onHand, int $reserved = 0): InventoryItem
{
    $product = Product::factory()->create($productAttributes);
    $variant = $product->variants()->create(array_merge([
        'price_amount' => 1000,
        'position' => 0,
        'is_default' => true,
    ], $variantAttributes));

    return $variant->inventoryItem()->create([
        'store_id' => $product->store_id,
        'quantity_on_hand' => $onHand,
        'quantity_reserved' => $reserved,
    ]);
}

test('lists inventory items with product, sku, and stock levels', function () {
    $item = makeInventoryItem(
        ['store_id' => $this->store->id, 'title' => 'Blue Shirt'],
        ['sku' => 'BS-001'],
        45,
        3,
    );

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/inventory')
        ->assertOk()
        ->assertSee('Blue Shirt')
        ->assertSee('BS-001')
        ->assertSee('45')
        ->assertSee('3');
});

test('adjusts quantity via set and delta actions', function () {
    $item = makeInventoryItem(['store_id' => $this->store->id], ['sku' => 'ADJ-1'], 10);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('setQuantity', $item->id, 42)
        ->assertDispatched('toast');

    expect($item->refresh()->quantity_on_hand)->toBe(42);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('adjustQuantity', $item->id, 3)
        ->call('adjustQuantity', $item->id, -5);

    expect($item->refresh()->quantity_on_hand)->toBe(40);
});

test('does not drive on-hand quantity below zero when adjusting', function () {
    $item = makeInventoryItem(['store_id' => $this->store->id], ['sku' => 'ADJ-2'], 2);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)->call('adjustQuantity', $item->id, -10);

    expect($item->refresh()->quantity_on_hand)->toBe(0);
});

test('toggles the inventory policy and persists it', function () {
    $item = makeInventoryItem(['store_id' => $this->store->id], ['sku' => 'POL-1'], 5);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('togglePolicy', $item->id)
        ->assertDispatched('toast');

    expect($item->refresh()->policy)->toBe(InventoryPolicy::Continue);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)->call('togglePolicy', $item->id);

    expect($item->refresh()->policy)->toBe(InventoryPolicy::Deny);
});

test('filters low stock items', function () {
    makeInventoryItem(['store_id' => $this->store->id, 'title' => 'Full Stock'], ['sku' => 'FULL-1'], 20);
    makeInventoryItem(['store_id' => $this->store->id, 'title' => 'Low Stock'], ['sku' => 'LOW-1'], 3);
    makeInventoryItem(['store_id' => $this->store->id, 'title' => 'Empty Stock'], ['sku' => 'OUT-1'], 0);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('stockFilter', 'low_stock')
        ->assertSee('LOW-1')
        ->assertDontSee('FULL-1')
        ->assertDontSee('OUT-1');
});

test('searches inventory by sku and product title', function () {
    makeInventoryItem(['store_id' => $this->store->id, 'title' => 'Blue Shirt'], ['sku' => 'BS-100'], 5);
    makeInventoryItem(['store_id' => $this->store->id, 'title' => 'Red Hat'], ['sku' => 'RH-100'], 5);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('search', 'BS-100')
        ->assertSee('Blue Shirt')
        ->assertDontSee('Red Hat')
        ->set('search', 'Red Hat')
        ->assertSee('RH-100')
        ->assertDontSee('BS-100');
});

test('support role can view inventory but cannot adjust it', function () {
    $support = $this->createUserWithRole($this->store, 'support');
    $item = makeInventoryItem(['store_id' => $this->store->id], ['sku' => 'SUP-1'], 5);

    $this->actingAs($support)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/inventory')
        ->assertOk();

    Livewire::actingAs($support);
    Livewire::test(Index::class)
        ->call('setQuantity', $item->id, 99)
        ->assertForbidden();

    expect($item->refresh()->quantity_on_hand)->toBe(5);
});
