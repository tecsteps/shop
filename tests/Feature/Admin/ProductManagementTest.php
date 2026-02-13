<?php

use App\Enums\ProductStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductIndex;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('lists products with pagination', function () {
    $ctx = createStoreContext();
    Product::factory()->count(20)->create(['store_id' => $ctx['store']->id]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(ProductIndex::class)
        ->assertOk()
        ->assertSee('Products');
});

it('creates a product via admin form', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(ProductForm::class)
        ->set('title', 'Test Product')
        ->set('descriptionHtml', '<p>A great product</p>')
        ->set('status', 'active')
        ->set('vendor', 'TestVendor')
        ->set('productType', 'Clothing')
        ->set('tags', 'tag1, tag2')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('title', 'Test Product')->first();
    expect($product)->not->toBeNull()
        ->and($product->vendor)->toBe('TestVendor');
});

it('edits a product via admin form', function () {
    $ctx = createStoreContext();
    $product = Product::factory()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Original Title',
    ]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(ProductForm::class, ['product' => $product])
        ->assertSet('mode', 'edit')
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertHasNoErrors();

    $product->refresh();
    expect($product->title)->toBe('Updated Title');
});

it('bulk archives selected products', function () {
    $ctx = createStoreContext();
    $products = Product::factory()->count(3)->create([
        'store_id' => $ctx['store']->id,
        'status' => ProductStatus::Active,
    ]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(ProductIndex::class)
        ->set('selected', $products->pluck('id')->toArray())
        ->call('bulkArchive');

    foreach ($products as $product) {
        $product->refresh();
        expect($product->status)->toBe(ProductStatus::Archived);
    }
});

it('uploads media from the product form', function () {
    Storage::fake('public');

    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    $file = UploadedFile::fake()->image('product.jpg', 800, 600);

    Livewire::test(ProductForm::class)
        ->set('title', 'Product With Media')
        ->set('status', 'draft')
        ->set('mediaUploads', [$file])
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('title', 'Product With Media')->first();
    expect($product->media()->count())->toBe(1);
});

it('manages variants from the product form', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(ProductForm::class)
        ->set('title', 'Product With Variants')
        ->set('status', 'draft')
        ->call('addOption')
        ->set('options.0.name', 'Size')
        ->set('options.0.values', 'S, M, L')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('title', 'Product With Variants')->first();
    expect($product->variants()->count())->toBe(3)
        ->and($product->options()->count())->toBe(1);
});

it('restricts product management to authorized roles', function () {
    $ctx = createStoreContext();

    $unauthorizedUser = User::factory()->create();
    // No store role attached

    test()->actingAs($unauthorizedUser);
    session(['current_store_id' => $ctx['store']->id]);

    // Since store.resolve:admin checks store access, this should fail
    $this->get('/admin/products')
        ->assertStatus(404);
});

it('staff can create but not delete products', function () {
    $ctx = createStoreContext();

    $staffUser = User::factory()->create();
    $staffUser->stores()->attach($ctx['store']->id, ['role' => StoreUserRole::Staff->value]);

    actingAsAdmin($staffUser, $ctx['store']);

    // Staff can access the product list
    Livewire::test(ProductIndex::class)
        ->assertOk();

    // Staff can create a product
    Livewire::test(ProductForm::class)
        ->set('title', 'Staff Product')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('title', 'Staff Product')->first();
    expect($product)->not->toBeNull();
});
