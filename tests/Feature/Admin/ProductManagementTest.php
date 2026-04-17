<?php

use App\Enums\ProductStatus;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the products list page', function () {
    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.products.index'))
        ->assertOk();
});

it('lists products for the current store', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Shirt',
    ]);

    Livewire::actingAs($this->user)
        ->test(ProductsIndex::class)
        ->assertSee('Blue Shirt');
});

it('searches products by title', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Shirt',
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Red Hat',
    ]);

    Livewire::actingAs($this->user)
        ->test(ProductsIndex::class)
        ->set('search', 'Blue')
        ->assertSee('Blue Shirt')
        ->assertDontSee('Red Hat');
});

it('filters products by status', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Active Product',
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Draft Product',
        'status' => ProductStatus::Draft,
    ]);

    Livewire::actingAs($this->user)
        ->test(ProductsIndex::class)
        ->set('statusFilter', 'active')
        ->assertSee('Active Product')
        ->assertDontSee('Draft Product');
});

it('archives products in bulk', function () {
    $products = Product::factory()->count(2)->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);

    Livewire::actingAs($this->user)
        ->test(ProductsIndex::class)
        ->set('selectedIds', $products->pluck('id')->toArray())
        ->call('bulkArchive');

    expect(Product::query()->withoutGlobalScopes()->whereIn('id', $products->pluck('id'))->get())
        ->each(fn ($product) => $product->status->toBe(ProductStatus::Archived));
});

it('renders the product create form', function () {
    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.products.create'))
        ->assertOk();
});

it('creates a new product via form', function () {
    Livewire::actingAs($this->user)
        ->test(ProductForm::class)
        ->set('title', 'New Product')
        ->set('description', 'A description')
        ->set('status', 'draft')
        ->set('price', 2999)
        ->call('save')
        ->assertRedirect();

    expect(Product::query()->withoutGlobalScopes()->where('title', 'New Product')->exists())
        ->toBeTrue();
});

it('renders the product edit form', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.products.edit', $product))
        ->assertOk();
});
