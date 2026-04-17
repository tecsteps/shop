<?php

use App\Livewire\Admin\Inventory\Index;
use App\Models\Product;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('requires authentication for inventory index', function () {
    $this->get(route('admin.inventory.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders the inventory index page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.inventory.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('lists inventory items with product and variant info', function () {
    $product = Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Test Product',
    ]);

    $variant = $product->variants()->create([
        'title' => 'Default',
        'price_amount' => 1000,
        'sku' => 'TST-001',
        'position' => 1,
        'is_default' => true,
    ]);

    $variant->inventoryItem()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 25,
        'quantity_reserved' => 3,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->assertSee('Test Product')
        ->assertSee('TST-001');
});

it('updates inventory quantity inline', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = $product->variants()->create([
        'title' => 'Default',
        'price_amount' => 1000,
        'position' => 1,
        'is_default' => true,
    ]);
    $item = $variant->inventoryItem()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('updateQuantity', $item->id, 50);

    expect($item->fresh()->quantity_on_hand)->toBe(50);
});

it('filters by low stock', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $variant1 = $product->variants()->create([
        'title' => 'V1',
        'price_amount' => 1000,
        'position' => 1,
    ]);
    $variant1->inventoryItem()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 3,
        'quantity_reserved' => 0,
    ]);

    $variant2 = $product->variants()->create([
        'title' => 'V2',
        'price_amount' => 1000,
        'position' => 2,
    ]);
    $variant2->inventoryItem()->create([
        'store_id' => $this->ctx['store']->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->set('stockFilter', 'low_stock')
        ->assertSee('V1')
        ->assertDontSee('V2');
});
