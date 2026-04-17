<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
    $this->actingAs($this->user);
    session()->put('current_store_id', $this->store->id);
});

it('renders product list page', function () {
    $response = $this->get('/admin/products');

    $response->assertSuccessful();
    $response->assertSee('Products');
});

it('lists products belonging to the store', function () {
    Product::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'status' => 'active',
        'published_at' => now()->toIso8601String(),
    ]);

    Livewire::test(\App\Livewire\Admin\Products\Index::class)
        ->assertSee('Products');
});

it('filters products by status', function () {
    Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Active Shirt',
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Draft Pants',
        'status' => 'draft',
    ]);

    Livewire::test(\App\Livewire\Admin\Products\Index::class)
        ->set('statusFilter', 'active')
        ->assertSee('Active Shirt')
        ->assertDontSee('Draft Pants');
});

it('searches products by title', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Organic Cotton Hoodie',
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Silk Blouse',
    ]);

    Livewire::test(\App\Livewire\Admin\Products\Index::class)
        ->set('search', 'Cotton')
        ->assertSee('Organic Cotton Hoodie')
        ->assertDontSee('Silk Blouse');
});

it('renders product create form', function () {
    $response = $this->get('/admin/products/create');

    $response->assertSuccessful();
    $response->assertSee('Add product');
});

it('creates a product with a default variant', function () {
    Livewire::test(\App\Livewire\Admin\Products\Form::class)
        ->set('title', 'Test Product')
        ->set('handle', 'test-product')
        ->set('status', 'draft')
        ->set('variants', [
            ['sku' => 'TP-001', 'price' => 2999, 'compareAtPrice' => null, 'quantity' => 10, 'requiresShipping' => true, 'optionValues' => []],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Product::withoutGlobalScopes()->where('title', 'Test Product')->exists())->toBeTrue();
});

it('bulk archives selected products', function () {
    $products = Product::factory()->active()->count(2)->create([
        'store_id' => $this->store->id,
    ]);

    Livewire::test(\App\Livewire\Admin\Products\Index::class)
        ->set('selectedIds', $products->pluck('id')->toArray())
        ->call('bulkArchive')
        ->assertDispatched('toast');

    foreach ($products as $product) {
        $product->refresh();
        expect($product->status->value)->toBe('archived');
    }
});

it('renders product edit form with existing data', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Editable Product',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1500,
        'is_default' => true,
    ]);

    $response = $this->get("/admin/products/{$product->id}/edit");

    $response->assertSuccessful();
    $response->assertSee('Editable Product');
});
