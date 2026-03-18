<?php

use App\Enums\ProductStatus;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductIndex;
use App\Models\Collection;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->actingAs($this->ctx['user']);
    session(['current_store_id' => $this->ctx['store']->id]);
});

it('requires authentication to access products page', function () {
    auth()->logout();
    $this->get('/admin/products')->assertRedirect('/admin/login');
});

it('renders the products index page', function () {
    $this->get('/admin/products')
        ->assertStatus(200)
        ->assertSee('Products');
});

it('lists products with search', function () {
    Product::factory()->create(['store_id' => $this->ctx['store']->id, 'title' => 'Blue Shirt']);
    Product::factory()->create(['store_id' => $this->ctx['store']->id, 'title' => 'Red Hat']);

    $component = Livewire::test(ProductIndex::class);
    $component->assertSee('Blue Shirt');
    $component->assertSee('Red Hat');

    $component->set('search', 'Blue');
    $component->assertSee('Blue Shirt');
    $component->assertDontSee('Red Hat');
});

it('filters products by status', function () {
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Active Product',
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Draft Product',
        'status' => ProductStatus::Draft,
    ]);

    $component = Livewire::test(ProductIndex::class);
    $component->set('statusFilter', 'active');
    $component->assertSee('Active Product');
    $component->assertDontSee('Draft Product');
});

it('can bulk archive products', function () {
    $products = Product::factory()->count(2)->create([
        'store_id' => $this->ctx['store']->id,
        'status' => ProductStatus::Active,
    ]);

    $component = Livewire::test(ProductIndex::class);
    $component->set('selectedIds', $products->pluck('id')->toArray());
    $component->call('bulkArchive');

    expect(Product::where('status', ProductStatus::Archived)->count())->toBe(2);
});

it('can bulk set products active', function () {
    $products = Product::factory()->count(2)->create([
        'store_id' => $this->ctx['store']->id,
        'status' => ProductStatus::Draft,
    ]);

    $component = Livewire::test(ProductIndex::class);
    $component->set('selectedIds', $products->pluck('id')->toArray());
    $component->call('bulkSetActive');

    expect(Product::where('status', ProductStatus::Active)->count())->toBe(2);
});

it('can bulk delete products', function () {
    $products = Product::factory()->count(2)->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $component = Livewire::test(ProductIndex::class);
    $component->set('selectedIds', $products->pluck('id')->toArray());
    $component->call('bulkDelete');

    expect(Product::count())->toBe(0);
});

it('shows empty state when no products exist', function () {
    $component = Livewire::test(ProductIndex::class);
    $component->assertSee('Add your first product');
});

it('renders the product create form', function () {
    $this->get('/admin/products/create')
        ->assertStatus(200)
        ->assertSee('Add product');
});

it('creates a product with variants', function () {
    $component = Livewire::test(ProductForm::class);

    $component->set('title', 'Test Product');
    $component->set('handle', 'test-product');
    $component->set('status', 'active');
    $component->set('vendor', 'Test Vendor');
    $component->set('productType', 'Test Type');
    $component->set('tags', 'tag1, tag2');

    $component->set('variants', [[
        'sku' => 'TP-001',
        'price' => 2999,
        'compareAtPrice' => null,
        'quantity' => 10,
        'requiresShipping' => true,
        'optionValues' => 'Default',
    ]]);

    $component->call('save');

    $product = Product::where('title', 'Test Product')->first();
    expect($product)->not->toBeNull();
    expect($product->handle)->toBe('test-product');
    expect($product->status)->toBe(ProductStatus::Active);
    expect($product->vendor)->toBe('Test Vendor');
    expect($product->variants)->toHaveCount(1);
    expect($product->variants->first()->price_amount)->toBe(2999);
});

it('updates an existing product', function () {
    $product = Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Old Title',
        'handle' => 'old-title',
    ]);

    $component = Livewire::test(ProductForm::class, ['product' => $product]);
    $component->set('title', 'New Title');
    $component->call('save');

    $product->refresh();
    expect($product->title)->toBe('New Title');
});

it('validates required fields on product form', function () {
    $component = Livewire::test(ProductForm::class);
    $component->set('title', '');
    $component->set('handle', '');
    $component->call('save');
    $component->assertHasErrors(['title', 'handle']);
});

it('renders the product edit form with data', function () {
    $product = Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Existing Product',
        'handle' => 'existing-product',
    ]);

    $this->get("/admin/products/{$product->id}/edit")
        ->assertStatus(200)
        ->assertSee('Existing Product');
});

it('archives a product via delete action', function () {
    $product = Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'status' => ProductStatus::Active,
    ]);

    $component = Livewire::test(ProductForm::class, ['product' => $product]);
    $component->call('deleteProduct');

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Archived);
});

it('generates variants from options', function () {
    $component = Livewire::test(ProductForm::class);
    $component->set('options', [
        ['name' => 'Size', 'values' => 'S, M, L'],
    ]);
    $component->call('generateVariants');

    expect($component->get('variants'))->toHaveCount(3);
});

it('assigns product to collections', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $component = Livewire::test(ProductForm::class);
    $component->set('title', 'Collection Product');
    $component->set('handle', 'collection-product');
    $component->set('collectionIds', [$collection->id]);
    $component->set('variants', [[
        'sku' => '',
        'price' => 1000,
        'compareAtPrice' => null,
        'quantity' => 5,
        'requiresShipping' => true,
        'optionValues' => 'Default',
    ]]);
    $component->call('save');

    $product = Product::where('title', 'Collection Product')->first();
    expect($product->collections)->toHaveCount(1);
    expect($product->collections->first()->id)->toBe($collection->id);
});

it('sorts products by column', function () {
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'A Product',
    ]);
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Z Product',
    ]);

    $component = Livewire::test(ProductIndex::class);
    $component->call('sortBy', 'title');
    expect($component->get('sortField'))->toBe('title');
    expect($component->get('sortDirection'))->toBe('asc');

    $component->call('sortBy', 'title');
    expect($component->get('sortDirection'))->toBe('desc');
});
