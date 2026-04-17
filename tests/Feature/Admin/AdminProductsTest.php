<?php

use App\Enums\ProductStatus;
use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index;
use App\Models\Product;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('requires authentication for products index', function () {
    $this->get(route('admin.products.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders the products index page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.products.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('lists products for the current store', function () {
    Product::factory()->count(3)->create(['store_id' => $this->ctx['store']->id]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->assertSuccessful();
});

it('searches products by title', function () {
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Blue T-Shirt',
    ]);
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Red Hat',
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->set('search', 'Blue')
        ->assertSee('Blue T-Shirt')
        ->assertDontSee('Red Hat');
});

it('filters products by status', function () {
    Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Active Product',
    ]);
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Draft Product',
        'status' => ProductStatus::Draft,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->set('statusFilter', 'active')
        ->assertSee('Active Product')
        ->assertDontSee('Draft Product');
});

it('can bulk archive products', function () {
    $products = Product::factory()->count(2)->create([
        'store_id' => $this->ctx['store']->id,
        'status' => ProductStatus::Active,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->set('selectedIds', $products->pluck('id')->all())
        ->call('bulkArchive');

    foreach ($products as $product) {
        expect($product->fresh()->status)->toBe(ProductStatus::Archived);
    }
});

it('renders the product create page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.products.create'))
        ->assertOk()
        ->assertSeeLivewire(Form::class);
});

it('creates a new product', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class)
        ->set('title', 'New Test Product')
        ->set('status', 'draft')
        ->set('variants.0.price', '29.99')
        ->set('variants.0.quantity', '10')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Product::where('title', 'New Test Product')->exists())->toBeTrue();
});

it('validates required title on save', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title');
});

it('renders the product edit page', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertSeeLivewire(Form::class);
});

it('loads product data in edit mode', function () {
    $product = Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Existing Product',
        'vendor' => 'Test Vendor',
    ]);

    $variant = $product->variants()->create([
        'title' => 'Default',
        'price_amount' => 1999,
        'is_default' => true,
        'position' => 1,
    ]);

    $variant->inventoryItem()->create([
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class, ['product' => $product])
        ->assertSet('title', 'Existing Product')
        ->assertSet('vendor', 'Test Vendor');
});

it('generates variants from options', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class)
        ->set('options', [
            ['name' => 'Size', 'values' => 'S, M, L'],
        ])
        ->call('generateVariants')
        ->assertSet('variants.0.title', 'S')
        ->assertSet('variants.1.title', 'M')
        ->assertSet('variants.2.title', 'L');
});
