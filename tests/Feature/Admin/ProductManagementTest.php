<?php

use App\Enums\ProductStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Products;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createAdminWithStore(StoreUserRole $role = StoreUserRole::Owner): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $store->users()->attach($user, ['role' => $role->value]);
    app()->instance('current_store', $store);
    session()->put('current_store_id', $store->id);

    return [$user, $store];
}

function createProductWithVariant(Store $store, string $status = 'draft', int $price = 2500): Product
{
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => $status,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $price,
    ]);

    InventoryItem::factory()->withStock(10)->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
    ]);

    return $product;
}

it('lists products for current store', function () {
    [$user, $store] = createAdminWithStore();

    $product = createProductWithVariant($store);

    Livewire::actingAs($user)
        ->test(Products\Index::class)
        ->assertSee($product->title);
});

it('searches products by title', function () {
    [$user, $store] = createAdminWithStore();

    $matchProduct = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Special Widget',
    ]);

    $otherProduct = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Different Item',
    ]);

    Livewire::actingAs($user)
        ->test(Products\Index::class)
        ->set('search', 'Special')
        ->assertSee('Special Widget')
        ->assertDontSee('Different Item');
});

it('filters products by status', function () {
    [$user, $store] = createAdminWithStore();

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Active Product',
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Draft Product',
        'status' => ProductStatus::Draft,
    ]);

    Livewire::actingAs($user)
        ->test(Products\Index::class)
        ->set('statusFilter', 'active')
        ->assertSee('Active Product')
        ->assertDontSee('Draft Product');
});

it('creates a new product', function () {
    [$user, $store] = createAdminWithStore();

    Livewire::actingAs($user)
        ->test(Products\Form::class)
        ->set('title', 'My New Product')
        ->set('description_html', '<p>Description here</p>')
        ->set('vendor', 'Acme')
        ->set('product_type', 'Widget')
        ->set('tags', 'new, featured')
        ->set('price_amount', 1999)
        ->set('sku', 'SKU-001')
        ->call('save')
        ->assertRedirect();

    expect(Product::where('title', 'My New Product')->exists())->toBeTrue();
});

it('edits an existing product', function () {
    [$user, $store] = createAdminWithStore();

    $product = createProductWithVariant($store);

    Livewire::actingAs($user)
        ->test(Products\Form::class, ['product' => $product])
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertRedirect();

    expect($product->fresh()->title)->toBe('Updated Title');
});

it('archives a product via bulk action', function () {
    [$user, $store] = createAdminWithStore();

    $product = createProductWithVariant($store);

    Livewire::actingAs($user)
        ->test(Products\Index::class)
        ->set('selected', [$product->id])
        ->call('archiveSelected');

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

it('deletes a draft product', function () {
    [$user, $store] = createAdminWithStore();

    $product = createProductWithVariant($store, 'draft');

    Livewire::actingAs($user)
        ->test(Products\Index::class)
        ->set('selected', [$product->id])
        ->call('deleteSelected');

    expect(Product::find($product->id))->toBeNull();
});

it('denies product creation for Support role', function () {
    [$user, $store] = createAdminWithStore(StoreUserRole::Support);

    Livewire::actingAs($user)
        ->test(Products\Form::class)
        ->assertForbidden();
});

it('paginates product list', function () {
    [$user, $store] = createAdminWithStore();

    Product::factory(20)->create(['store_id' => $store->id]);

    Livewire::actingAs($user)
        ->test(Products\Index::class)
        ->assertStatus(200);
});

it('requires authentication for products', function () {
    $response = $this->get(route('admin.products.index'));

    $response->assertRedirect();
});
