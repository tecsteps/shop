<?php

use App\Enums\ProductStatus;
use App\Livewire\Admin\Products\Index;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createAdminWithStore(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the products index page', function () {
    [$user, $store] = createAdminWithStore();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Products')
        ->assertSee('Create product')
        ->assertSuccessful();
});

test('it lists products for the current store', function () {
    [$user, $store] = createAdminWithStore();

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Test Product Alpha',
        'status' => ProductStatus::Active,
    ]);

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Test Product Beta',
        'status' => ProductStatus::Draft,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Test Product Alpha')
        ->assertSee('Test Product Beta');
});

test('it does not show products from other stores', function () {
    [$user, $store] = createAdminWithStore();

    $otherStore = Store::factory()->create();

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'My Store Product',
    ]);

    Product::factory()->create([
        'store_id' => $otherStore->id,
        'title' => 'Other Store Product',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('My Store Product')
        ->assertDontSee('Other Store Product');
});

test('it filters products by search term', function () {
    [$user, $store] = createAdminWithStore();

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Red Running Shoes',
    ]);

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Blue T-Shirt',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'Running')
        ->assertSee('Red Running Shoes')
        ->assertDontSee('Blue T-Shirt');
});

test('it filters products by status', function () {
    [$user, $store] = createAdminWithStore();

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Active Product',
        'status' => ProductStatus::Active,
    ]);

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Draft Product',
        'status' => ProductStatus::Draft,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('status', 'active')
        ->assertSee('Active Product')
        ->assertDontSee('Draft Product');
});

test('it displays product price from default variant', function () {
    [$user, $store] = createAdminWithStore();

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Priced Product',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2499,
        'is_default' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('24.99 EUR');
});

test('it displays inventory total from variants', function () {
    [$user, $store] = createAdminWithStore();

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Stocked Product',
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 42,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('42 in stock');
});

test('it can delete a draft product', function () {
    [$user, $store] = createAdminWithStore();

    $product = Product::factory()->draft()->create([
        'store_id' => $store->id,
        'title' => 'Deletable Product',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('confirmDelete', $product->id, 'Deletable Product')
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingProductTitle', 'Deletable Product')
        ->call('deleteProduct')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('toast');

    expect(Product::find($product->id))->toBeNull();
});

test('it cannot delete an active product', function () {
    [$user, $store] = createAdminWithStore();

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Active Product',
        'status' => ProductStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('confirmDelete', $product->id, 'Active Product')
        ->call('deleteProduct')
        ->assertDispatched('toast');

    expect(Product::find($product->id))->not->toBeNull();
});

test('it shows empty state when no products exist', function () {
    [$user, $store] = createAdminWithStore();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('No products found.');
});

test('it displays colored status badges', function () {
    [$user, $store] = createAdminWithStore();

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Active Badge Product',
        'status' => ProductStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Active Badge Product')
        ->assertSee('Active');
});
