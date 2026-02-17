<?php

use App\Enums\ProductStatus;
use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index;
use App\Models\Product;
use Livewire\Livewire;

it('renders the products list page', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Product::factory()->count(3)->create(['store_id' => $ctx['store']->id]);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Products');
});

it('searches products by title', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Product::factory()->create(['store_id' => $ctx['store']->id, 'title' => 'Blue Widget']);
    Product::factory()->create(['store_id' => $ctx['store']->id, 'title' => 'Red Widget']);

    Livewire::test(Index::class)
        ->set('search', 'Blue')
        ->assertSee('Blue Widget')
        ->assertDontSee('Red Widget');
});

it('filters products by status', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Product::factory()->active()->create(['store_id' => $ctx['store']->id, 'title' => 'Active Product']);
    Product::factory()->create(['store_id' => $ctx['store']->id, 'title' => 'Draft Product', 'status' => ProductStatus::Draft]);

    Livewire::test(Index::class)
        ->set('statusFilter', 'active')
        ->assertSee('Active Product')
        ->assertDontSee('Draft Product');
});

it('creates a new product', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Form::class)
        ->set('title', 'Test Product')
        ->set('handle', 'test-product')
        ->set('status', 'draft')
        ->call('save')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('products', [
        'store_id' => $ctx['store']->id,
        'title' => 'Test Product',
        'handle' => 'test-product',
    ]);
});

it('edits an existing product', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    $product = Product::factory()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Original Title',
        'handle' => 'original-title',
    ]);

    Livewire::test(Form::class, ['product' => $product])
        ->assertSet('title', 'Original Title')
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertDispatched('toast');

    expect($product->fresh()->title)->toBe('Updated Title');
});

it('validates product title is required', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Form::class)
        ->set('title', '')
        ->set('handle', 'some-handle')
        ->call('save')
        ->assertHasErrors(['title']);
});

it('performs bulk archive on selected products', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    $products = Product::factory()->active()->count(2)->create(['store_id' => $ctx['store']->id]);

    Livewire::test(Index::class)
        ->set('selectedIds', $products->pluck('id')->toArray())
        ->call('bulkArchive')
        ->assertDispatched('toast');

    foreach ($products as $product) {
        expect($product->fresh()->status)->toBe(ProductStatus::Archived);
    }
});

it('generates variants from options', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Form::class)
        ->set('title', 'Variant Product')
        ->set('handle', 'variant-product')
        ->call('addOption')
        ->set('options.0.name', 'Size')
        ->set('options.0.values', 'S, M, L')
        ->call('generateVariants')
        ->assertCount('variants', 3);
});
