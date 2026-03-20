<?php

use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    session()->put('current_store_id', $this->context['store']->id);
});

it('renders the products index', function () {
    $this->actingAs($this->context['user'])
        ->get('/admin/products')
        ->assertOk()
        ->assertSeeLivewire(ProductsIndex::class);
});

it('lists products for the store', function () {
    Product::factory()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Test Product Alpha',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(ProductsIndex::class)
        ->assertSee('Test Product Alpha');
});

it('searches products by title', function () {
    Product::factory()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Blue Widget',
    ]);
    Product::factory()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Red Gadget',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(ProductsIndex::class)
        ->set('search', 'Blue')
        ->assertSee('Blue Widget')
        ->assertDontSee('Red Gadget');
});

it('filters products by status', function () {
    Product::factory()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Active Item',
        'status' => 'active',
    ]);
    Product::factory()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Draft Item',
        'status' => 'draft',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(ProductsIndex::class)
        ->set('statusFilter', 'active')
        ->assertSee('Active Item')
        ->assertDontSee('Draft Item');
});

it('deletes a draft product', function () {
    $product = Product::factory()->create([
        'store_id' => $this->context['store']->id,
        'status' => 'draft',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(ProductsIndex::class)
        ->call('deleteProduct', $product->id);

    expect(Product::find($product->id))->toBeNull();
});

it('prevents deleting non-draft products', function () {
    $product = Product::factory()->create([
        'store_id' => $this->context['store']->id,
        'status' => 'active',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(ProductsIndex::class)
        ->call('deleteProduct', $product->id);

    expect(Product::find($product->id))->not->toBeNull();
});

it('renders the product create form', function () {
    $this->actingAs($this->context['user'])
        ->get('/admin/products/create')
        ->assertOk()
        ->assertSeeLivewire(ProductForm::class);
});

it('creates a new product', function () {
    Livewire::actingAs($this->context['user'])
        ->test(ProductForm::class)
        ->set('title', 'New Test Product')
        ->set('description', 'A great product')
        ->set('vendor', 'TestVendor')
        ->call('save')
        ->assertRedirect();

    $this->assertDatabaseHas('products', [
        'store_id' => $this->context['store']->id,
        'title' => 'New Test Product',
        'vendor' => 'TestVendor',
    ]);
});

it('edits an existing product', function () {
    $product = Product::factory()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Old Title',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(ProductForm::class, ['productId' => $product->id])
        ->assertSet('title', 'Old Title')
        ->set('title', 'Updated Title')
        ->call('save');

    expect($product->fresh()->title)->toBe('Updated Title');
});

it('validates required fields on product form', function () {
    Livewire::actingAs($this->context['user'])
        ->test(ProductForm::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title');
});
