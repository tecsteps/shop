<?php

use App\Enums\ProductStatus;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductIndex;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('lists products for the current store only', function (): void {
    [$user, $store] = loginAsAdmin();

    Product::factory()->create(['store_id' => $store->id, 'title' => 'Alpha Shirt']);

    $otherStore = \App\Models\Store::factory()->create();
    Product::factory()->create(['store_id' => $otherStore->id, 'title' => 'Omega Shoes']);

    Livewire::test(ProductIndex::class)
        ->assertSee('Alpha Shirt')
        ->assertDontSee('Omega Shoes');
});

it('filters products by search term', function (): void {
    [$user, $store] = loginAsAdmin();

    Product::factory()->create(['store_id' => $store->id, 'title' => 'Red Hat']);
    Product::factory()->create(['store_id' => $store->id, 'title' => 'Blue Jacket']);

    Livewire::test(ProductIndex::class)
        ->set('search', 'Red')
        ->assertSee('Red Hat')
        ->assertDontSee('Blue Jacket');
});

it('creates a product via the form', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(ProductForm::class)
        ->set('title', 'Test Sneaker')
        ->set('status', 'active')
        ->set('priceAmount', 1999)
        ->set('sku', 'TS-001')
        ->call('save')
        ->assertRedirect(route('admin.products.index'));

    $product = Product::where('title', 'Test Sneaker')->first();
    expect($product)->not->toBeNull()
        ->and($product->store_id)->toBe($store->id)
        ->and($product->variants()->first()->price_amount)->toBe(1999);
});

it('edits an existing product', function (): void {
    [$user, $store] = loginAsAdmin();

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Old Name',
    ]);
    $product->variants()->create([
        'price_amount' => 1000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);

    Livewire::test(ProductForm::class, ['product' => $product])
        ->set('title', 'New Name')
        ->set('priceAmount', 2500)
        ->call('save')
        ->assertRedirect(route('admin.products.index'));

    expect($product->fresh()->title)->toBe('New Name');
});

it('archives selected products in bulk', function (): void {
    [$user, $store] = loginAsAdmin();

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active->value,
    ]);

    Livewire::test(ProductIndex::class)
        ->set('selectedIds', [$product->id])
        ->call('bulkArchive');

    expect($product->fresh()->status->value)->toBe('archived');
});
