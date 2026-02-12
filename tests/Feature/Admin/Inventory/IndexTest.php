<?php

use App\Livewire\Admin\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function inventoryAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

function createInventoryItem(Store $store, string $productTitle = 'Test Product', string $sku = 'SKU-001', int $quantity = 10): InventoryItem
{
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => $productTitle,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => $sku,
        'is_default' => true,
    ]);

    return InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $quantity,
    ]);
}

test('it renders the inventory index page', function () {
    [$user, $store] = inventoryAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Inventory')
        ->assertSuccessful();
});

test('it lists inventory items with product and variant info', function () {
    [$user, $store] = inventoryAdmin();

    createInventoryItem($store, 'Widget Alpha', 'WDG-001', 25);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Widget Alpha')
        ->assertSee('WDG-001');
});

test('it does not show inventory from other stores', function () {
    [$user, $store] = inventoryAdmin();

    $otherStore = Store::factory()->create();

    createInventoryItem($store, 'My Item', 'MY-001');
    createInventoryItem($otherStore, 'Other Item', 'OTHER-001');

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('My Item')
        ->assertDontSee('Other Item');
});

test('it filters inventory by search term on SKU', function () {
    [$user, $store] = inventoryAdmin();

    createInventoryItem($store, 'Product A', 'SKU-ALPHA', 10);
    createInventoryItem($store, 'Product B', 'SKU-BETA', 20);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'ALPHA')
        ->assertSee('Product A')
        ->assertDontSee('Product B');
});

test('it filters inventory by search term on product title', function () {
    [$user, $store] = inventoryAdmin();

    createInventoryItem($store, 'Red Running Shoes', 'RRS-001');
    createInventoryItem($store, 'Blue T-Shirt', 'BTS-001');

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'Running')
        ->assertSee('Red Running Shoes')
        ->assertDontSee('Blue T-Shirt');
});

test('it updates inventory quantity inline', function () {
    [$user, $store] = inventoryAdmin();

    $item = createInventoryItem($store, 'Updatable Item', 'UPD-001', 10);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set("editingQuantities.{$item->id}", 50)
        ->call('updateQuantity', $item->id)
        ->assertDispatched('toast');

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(50);
});
