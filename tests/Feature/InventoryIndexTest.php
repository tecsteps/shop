<?php

use App\Livewire\Admin\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

function inventoryIndexItem(Store $store, string $title, string $sku, int $quantity, int $reserved = 0, string $policy = 'deny'): InventoryItem
{
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => $title,
        'handle' => str($title)->slug().'-'.fake()->unique()->numberBetween(1, 100000),
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'sku' => $sku,
        'position' => 0,
        'is_default' => true,
    ]);

    return $variant->inventoryItem()->create([
        'store_id' => $store->id,
        'quantity_on_hand' => $quantity,
        'quantity_reserved' => $reserved,
        'policy' => $policy,
    ]);
}

function inventoryIndexUser(Store $store, string $role = 'staff'): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => $role]);

    return $user;
}

it('shows only inventory belonging to the current store', function () {
    $store = shopStore();
    $localInventory = inventoryIndexItem($store, 'Local Linen Shirt', 'LOCAL-001', 12);
    $otherStore = shopStore('other-inventory.test');
    inventoryIndexItem($otherStore, 'Foreign Wool Hat', 'FOREIGN-001', 7);
    $user = inventoryIndexUser($store);
    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Local Linen Shirt')
        ->assertSee('LOCAL-001')
        ->assertDontSee('Foreign Wool Hat')
        ->assertDontSee('FOREIGN-001');
});

it('lets support staff view stock without exposing inventory controls', function () {
    $store = shopStore();
    inventoryIndexItem($store, 'Support View Product', 'SUPPORT-VIEW-001', 5);
    $user = inventoryIndexUser($store, 'support');
    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Support View Product')
        ->assertSee('5')
        ->assertDontSee('Save')
        ->assertDontSee('inventory-quantity-');
});

it('searches inventory by product title and SKU', function () {
    $store = shopStore();
    inventoryIndexItem($store, 'Azure Trail Jacket', 'JACKET-204', 9);
    inventoryIndexItem($store, 'Canvas Weekend Bag', 'BAG-883', 4);
    $user = inventoryIndexUser($store);
    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'Azure Trail')
        ->assertSee('Azure Trail Jacket')
        ->assertDontSee('Canvas Weekend Bag')
        ->set('search', 'BAG-883')
        ->assertSee('Canvas Weekend Bag')
        ->assertSee('BAG-883')
        ->assertDontSee('Azure Trail Jacket');
});

it('filters rows by available stock level', function () {
    $store = shopStore();
    inventoryIndexItem($store, 'Ready to Ship Mug', 'MUG-READY', 12, 2);
    inventoryIndexItem($store, 'Low Stock Scarf', 'SCARF-LOW', 5);
    inventoryIndexItem($store, 'Sold Out Candle', 'CANDLE-OUT', 0);
    $user = inventoryIndexUser($store);
    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('stockFilter', 'in_stock')
        ->assertSee('Ready to Ship Mug')
        ->assertSee('Low Stock Scarf')
        ->assertDontSee('Sold Out Candle')
        ->set('stockFilter', 'low_stock')
        ->assertSee('Low Stock Scarf')
        ->assertDontSee('Ready to Ship Mug')
        ->assertDontSee('Sold Out Candle')
        ->set('stockFilter', 'out_of_stock')
        ->assertSee('Sold Out Candle')
        ->assertDontSee('Ready to Ship Mug')
        ->assertDontSee('Low Stock Scarf');
});

it('saves validated quantity and policy changes for a permitted store user', function () {
    $store = shopStore();
    $inventoryItem = inventoryIndexItem($store, 'Updateable Travel Cup', 'CUP-900', 8);
    $user = inventoryIndexUser($store, 'staff');
    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set("quantities.{$inventoryItem->id}", '3')
        ->set("policies.{$inventoryItem->id}", 'continue')
        ->call('saveInventoryItem', $inventoryItem->id)
        ->assertHasNoErrors();

    expect($inventoryItem->refresh()->quantity_on_hand)->toBe(3)
        ->and($inventoryItem->policy)->toBe('continue');
});

it('denies quantity and policy changes when the product update permission fails', function () {
    $store = shopStore();
    $inventoryItem = inventoryIndexItem($store, 'Protected Stock Item', 'PRIVATE-001', 6);
    $user = inventoryIndexUser($store);
    app()->instance('current_store', $store);
    Gate::before(fn (User $actingUser, string $ability): ?bool => $actingUser->is($user) && $ability === 'update' ? false : null);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set("quantities.{$inventoryItem->id}", '1')
        ->set("policies.{$inventoryItem->id}", 'continue')
        ->call('saveInventoryItem', $inventoryItem->id)
        ->assertForbidden();

    expect($inventoryItem->refresh()->quantity_on_hand)->toBe(6)
        ->and($inventoryItem->policy)->toBe('deny');
});

it('rejects invalid stock values without persisting changes', function () {
    $store = shopStore();
    $inventoryItem = inventoryIndexItem($store, 'Validated Hiking Socks', 'SOCK-VALIDATE', 6);
    $user = inventoryIndexUser($store);
    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set("quantities.{$inventoryItem->id}", '-1')
        ->set("policies.{$inventoryItem->id}", 'sell-everything')
        ->call('saveInventoryItem', $inventoryItem->id)
        ->assertHasErrors([
            "quantities.{$inventoryItem->id}" => 'min',
            "policies.{$inventoryItem->id}" => 'in',
        ]);

    expect($inventoryItem->refresh()->quantity_on_hand)->toBe(6)
        ->and($inventoryItem->policy)->toBe('deny');
});
