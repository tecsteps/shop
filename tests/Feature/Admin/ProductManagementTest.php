<?php

use App\Enums\MediaStatus;
use App\Enums\ProductStatus;
use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('lists products with pagination', function () {
    foreach (range(1, 25) as $i) {
        Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => 'Product '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'updated_at' => now()->subMinutes($i),
        ]);
    }

    // Default sort is updated_at desc: page 1 holds Products 01-15.
    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/products')
        ->assertOk()
        ->assertSee('Product 01')
        ->assertSee('Product 15')
        ->assertDontSee('Product 16')
        ->assertDontSee('Product 25');
});

test('searches products by title', function () {
    Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Blue Shirt']);
    Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Red Hat']);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('search', 'Blue')
        ->assertSee('Blue Shirt')
        ->assertDontSee('Red Hat');
});

test('filters products by status', function () {
    Product::factory()->draft()->create(['store_id' => $this->store->id, 'title' => 'Draft Product']);
    Product::factory()->active()->create(['store_id' => $this->store->id, 'title' => 'Active Product']);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('statusFilter', 'active')
        ->assertSee('Active Product')
        ->assertDontSee('Draft Product');
});

test('creates a product via admin form', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('title', 'Blue Shirt')
        ->set('descriptionHtml', '<p>A nice shirt</p>')
        ->set('variants.0.sku', 'BS-001')
        ->set('variants.0.price', 1999)
        ->set('variants.0.quantity', 7)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $product = Product::query()->where('title', 'Blue Shirt')->sole();

    expect($product->handle)->toBe('blue-shirt')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->sku)->toBe('BS-001')
        ->and($product->variants->first()->price_amount)->toBe(1999)
        ->and($product->variants->first()->inventoryItem->quantity_on_hand)->toBe(7);
});

test('creates a product with options and generated variants', function () {
    Livewire::actingAs($this->user);
    $component = Livewire::test(Form::class)
        ->set('title', 'Sized Shirt')
        ->set('options', [['name' => 'Size', 'values' => 'S, M']])
        ->call('generateVariants')
        ->assertCount('variants', 2)
        ->set('variants.0.price', 1000)
        ->set('variants.1.price', 1200)
        ->set('variants.0.sku', 'SS-S')
        ->set('variants.1.sku', 'SS-M')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('title', 'Sized Shirt')->sole();

    expect($product->options)->toHaveCount(1)
        ->and($product->options->first()->values->pluck('value')->all())->toBe(['S', 'M'])
        ->and($product->variants)->toHaveCount(2)
        ->and($product->variants->pluck('sku')->sort()->values()->all())->toBe(['SS-M', 'SS-S'])
        ->and($product->variants->every(fn ($variant): bool => $variant->inventoryItem !== null))->toBeTrue();
});

test('edits a product via admin form', function () {
    $product = Product::factory()->withVariants(1)->create([
        'store_id' => $this->store->id,
        'title' => 'Old Title',
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class, ['product' => $product])
        ->assertSet('title', 'Old Title')
        ->set('title', 'New Title')
        ->set('variants.0.price', 4321)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $product->refresh();

    expect($product->title)->toBe('New Title')
        ->and($product->variants->first()->price_amount)->toBe(4321);
});

test('surfaces SKU uniqueness errors from the form', function () {
    Product::factory()->withVariants(1, ['sku' => 'TAKEN-001'])->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('title', 'Another Product')
        ->set('variants.0.sku', 'TAKEN-001')
        ->set('variants.0.price', 100)
        ->call('save')
        ->assertHasErrors(['variants.0.sku']);
});

test('bulk archives selected products', function () {
    $products = Product::factory()->active()->count(3)->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('selectedIds', $products->pluck('id')->all())
        ->call('bulkArchive')
        ->assertDispatched('toast');

    foreach ($products as $product) {
        expect($product->refresh()->status)->toBe(ProductStatus::Archived);
    }
});

test('deletes a draft product but blocks an ordered product', function () {
    $draft = Product::factory()->draft()->create(['store_id' => $this->store->id]);
    $ordered = Product::factory()->draft()->create(['store_id' => $this->store->id]);

    createOrderLineFor($this->store, $ordered);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('selectedIds', [$draft->id, $ordered->id])
        ->call('bulkDelete')
        ->assertDispatched('toast');

    $this->assertDatabaseMissing('products', ['id' => $draft->id]);
    $this->assertDatabaseHas('products', ['id' => $ordered->id]);
});

test('uploads media from the product form', function () {
    Storage::fake('public');

    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class, ['product' => $product])
        ->set('newMedia', [$file])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('product_media', [
        'product_id' => $product->id,
        'status' => MediaStatus::Ready->value,
    ]);

    $media = $product->media()->sole();
    expect(Storage::disk('public')->exists($media->storage_key))->toBeTrue()
        ->and(Storage::disk('public')->exists($media->pathFor('thumbnail')))->toBeTrue();
});

test('support role can view products but cannot create', function () {
    $support = $this->createUserWithRole($this->store, 'support');

    $this->actingAs($support)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/products')
        ->assertOk();

    $this->actingAs($support)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/products/create')
        ->assertForbidden();
});

test('staff can create but not delete products', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');

    Livewire::actingAs($staff);
    Livewire::test(Form::class)
        ->set('title', 'Staff Product')
        ->set('variants.0.price', 100)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', ['title' => 'Staff Product']);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($staff);
    Livewire::test(Form::class, ['product' => $product])
        ->call('deleteProduct')
        ->assertForbidden();

    expect($product->refresh()->status)->toBe(ProductStatus::Active);
});
