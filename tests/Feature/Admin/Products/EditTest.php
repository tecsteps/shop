<?php

use App\Enums\ProductStatus;
use App\Livewire\Admin\Products\Edit;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupEditAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

function createProductWithVariant(Store $store, array $productAttributes = [], array $variantAttributes = []): Product
{
    $product = Product::factory()->create(array_merge([
        'store_id' => $store->id,
    ], $productAttributes));

    $variant = ProductVariant::factory()->create(array_merge([
        'product_id' => $product->id,
        'is_default' => true,
        'price_amount' => 2999,
    ], $variantAttributes));

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
    ]);

    return $product;
}

test('it renders the edit product page with pre-populated data', function () {
    [$user, $store] = setupEditAdmin();

    $product = createProductWithVariant($store, [
        'title' => 'Editable Product',
        'description_html' => '<p>A description.</p>',
        'product_type' => 'Shoes',
        'vendor' => 'Nike',
        'tags' => ['sale', 'trending'],
        'status' => ProductStatus::Active,
    ], [
        'price_amount' => 4999,
        'compare_at_amount' => 5999,
        'sku' => 'SKU-EDIT-001',
        'barcode' => '9876543210123',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['product' => $product])
        ->assertSet('title', 'Editable Product')
        ->assertSet('descriptionHtml', '<p>A description.</p>')
        ->assertSet('productType', 'Shoes')
        ->assertSet('vendor', 'Nike')
        ->assertSet('tags', 'sale, trending')
        ->assertSet('status', 'active')
        ->assertSet('price', '49.99')
        ->assertSet('compareAtPrice', '59.99')
        ->assertSet('sku', 'SKU-EDIT-001')
        ->assertSet('barcode', '9876543210123')
        ->assertSet('quantity', 10)
        ->assertSuccessful();
});

test('it updates product fields', function () {
    [$user, $store] = setupEditAdmin();

    $product = createProductWithVariant($store, [
        'title' => 'Original Title',
        'status' => ProductStatus::Draft,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['product' => $product])
        ->set('title', 'Updated Title')
        ->set('vendor', 'New Vendor')
        ->call('save')
        ->assertDispatched('toast');

    $product->refresh();

    expect($product->title)->toBe('Updated Title')
        ->and($product->vendor)->toBe('New Vendor');
});

test('it updates variant price in cents', function () {
    [$user, $store] = setupEditAdmin();

    $product = createProductWithVariant($store, [
        'status' => ProductStatus::Draft,
    ], [
        'price_amount' => 1000,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['product' => $product])
        ->set('price', '55.50')
        ->call('save')
        ->assertDispatched('toast');

    $variant = $product->variants()->where('is_default', true)->first();

    expect($variant->price_amount)->toBe(5550);
});

test('it updates inventory quantity', function () {
    [$user, $store] = setupEditAdmin();

    $product = createProductWithVariant($store);

    Livewire::actingAs($user)
        ->test(Edit::class, ['product' => $product])
        ->set('quantity', 100)
        ->call('save')
        ->assertDispatched('toast');

    $variant = $product->variants()->where('is_default', true)->first();

    expect($variant->inventoryItem->quantity_on_hand)->toBe(100);
});

test('it validates required fields on update', function () {
    [$user, $store] = setupEditAdmin();

    $product = createProductWithVariant($store);

    Livewire::actingAs($user)
        ->test(Edit::class, ['product' => $product])
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('it can delete a draft product from edit page', function () {
    [$user, $store] = setupEditAdmin();

    $product = createProductWithVariant($store, [
        'title' => 'Draft To Delete',
        'status' => ProductStatus::Draft,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['product' => $product])
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', true)
        ->call('deleteProduct')
        ->assertDispatched('toast')
        ->assertRedirect(route('admin.products.index'));

    expect(Product::find($product->id))->toBeNull();
});

test('it cannot delete an active product from edit page', function () {
    [$user, $store] = setupEditAdmin();

    $product = createProductWithVariant($store, [
        'status' => ProductStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['product' => $product])
        ->call('confirmDelete')
        ->call('deleteProduct')
        ->assertDispatched('toast');

    expect(Product::find($product->id))->not->toBeNull();
});

test('it shows the variants table', function () {
    [$user, $store] = setupEditAdmin();

    $product = createProductWithVariant($store, [], [
        'sku' => 'VAR-SKU-001',
        'price_amount' => 1999,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['product' => $product])
        ->assertSee('Variants')
        ->assertSee('VAR-SKU-001')
        ->assertSee('19.99 EUR');
});

test('it aborts if product belongs to a different store', function () {
    [$user, $store] = setupEditAdmin();

    $otherStore = Store::factory()->create();
    $product = Product::factory()->create([
        'store_id' => $otherStore->id,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['product' => $product])
        ->assertNotFound();
});
