<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Inventory\Index as InventoryIndex;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('lists inventory items with variant and product details', function () {
    createPurchasableVariant($this->store, quantityOnHand: 12, variantAttributes: ['sku' => 'SKU-AAA']);
    createPurchasableVariant($this->store, quantityOnHand: 0, variantAttributes: ['sku' => 'SKU-BBB']);

    actingAsAdmin($this->user)
        ->get('/admin/inventory')
        ->assertOk();

    $component = Livewire::test(InventoryIndex::class);

    expect($component->instance()->inventoryItems()->total())->toBe(2);
    $component->assertSee('SKU-AAA')->assertSee('SKU-BBB');
});

it('searches inventory by sku and product title', function () {
    $variant = createPurchasableVariant($this->store, variantAttributes: ['sku' => 'FIND-ME-1']);
    createPurchasableVariant($this->store, variantAttributes: ['sku' => 'OTHER-2']);

    actingAsAdmin($this->user);

    $component = Livewire::test(InventoryIndex::class)->set('search', 'FIND-ME');

    expect($component->instance()->inventoryItems()->total())->toBe(1);

    $component->set('search', $variant->product->title);

    expect($component->instance()->inventoryItems()->total())->toBe(1);
});

it('filters inventory by stock level', function () {
    createPurchasableVariant($this->store, quantityOnHand: 50, variantAttributes: ['sku' => 'PLENTY']);
    createPurchasableVariant($this->store, quantityOnHand: 3, variantAttributes: ['sku' => 'LOW']);
    createPurchasableVariant($this->store, quantityOnHand: 0, variantAttributes: ['sku' => 'OUT']);

    actingAsAdmin($this->user);

    $component = Livewire::test(InventoryIndex::class);

    $component->set('stockFilter', 'low_stock');
    expect($component->instance()->inventoryItems()->getCollection()->first()->variant->sku)->toBe('LOW');

    $component->set('stockFilter', 'out_of_stock');
    expect($component->instance()->inventoryItems()->getCollection()->first()->variant->sku)->toBe('OUT');

    $component->set('stockFilter', 'in_stock');
    expect($component->instance()->inventoryItems()->total())->toBe(2);
});

it('adjusts the on-hand quantity inline', function () {
    $variant = createPurchasableVariant($this->store, quantityOnHand: 10);
    $item = $variant->inventoryItem()->firstOrFail();

    actingAsAdmin($this->user);

    Livewire::test(InventoryIndex::class)
        ->call('updateQuantity', $item->getKey(), '25')
        ->assertDispatched('toast');

    expect($item->refresh()->quantity_on_hand)->toBe(25);
});

it('clamps negative quantities to zero', function () {
    $variant = createPurchasableVariant($this->store, quantityOnHand: 10);
    $item = $variant->inventoryItem()->firstOrFail();

    actingAsAdmin($this->user);

    Livewire::test(InventoryIndex::class)
        ->call('updateQuantity', $item->getKey(), '-5');

    expect($item->refresh()->quantity_on_hand)->toBe(0);
});

it('restricts inventory adjustments for support users', function () {
    $variant = createPurchasableVariant($this->store, quantityOnHand: 10);
    $item = $variant->inventoryItem()->firstOrFail();

    $support = createStoreMember($this->store, StoreUserRole::Support);

    actingAsAdmin($support, $this->store)
        ->get('/admin/inventory')
        ->assertOk();

    Livewire::test(InventoryIndex::class)
        ->call('updateQuantity', $item->getKey(), '99')
        ->assertForbidden();

    expect($item->refresh()->quantity_on_hand)->toBe(10);
});
