<?php

use App\Enums\InventoryPolicy;
use App\Livewire\Admin\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('lists inventory scoped to the current store', function () {
    $variant = CommerceTestHelper::purchasableVariant($this->store, ['inventory' => ['quantity_on_hand' => 3, 'policy' => 'deny']]);

    $this->get(route('admin.inventory.index'))
        ->assertOk()
        ->assertSee($variant->product->title);
});

it('updates quantity through the inventory service', function () {
    $variant = CommerceTestHelper::purchasableVariant($this->store);
    $item = $variant->inventoryItem;

    Livewire::test(Index::class)
        ->call('updateQuantity', $item->getKey(), 25)
        ->assertDispatched('toast');

    expect($item->refresh()->quantity_on_hand)->toBe(25);
});

it('updates the inventory policy', function () {
    $variant = CommerceTestHelper::purchasableVariant($this->store);
    $item = $variant->inventoryItem;

    Livewire::test(Index::class)
        ->call('updatePolicy', $item->getKey(), 'continue')
        ->assertDispatched('toast');

    expect($item->refresh()->policy)->toBe(InventoryPolicy::Continue);
});

it('filters by stock level', function () {
    $low = CommerceTestHelper::purchasableVariant($this->store, [], ['quantity_on_hand' => 2]);
    CommerceTestHelper::purchasableVariant($this->store, [], ['quantity_on_hand' => 50]);

    Livewire::test(Index::class)
        ->set('stockFilter', 'low_stock')
        ->assertSee($low->product->title);
});

it('rejects updates for items from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($otherStore);
    $itemId = $variant->inventoryItem->getKey();
    $this->actingAsAdmin($this->user, $this->store);

    try {
        Livewire::test(Index::class)->call('updateQuantity', $itemId, 5);
        $this->fail('Expected cross-store inventory update to fail.');
    } catch (ModelNotFoundException) {
        // Expected: item is scoped to the other store.
    }

    expect(InventoryItem::query()->withoutGlobalScope(StoreScope::class)->find($itemId)->quantity_on_hand)->toBe(50);
});
