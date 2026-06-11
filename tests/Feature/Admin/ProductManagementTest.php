<?php

use App\Enums\ProductStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('lists products with pagination', function () {
    Product::factory()->count(25)->for($this->store)->create();

    actingAsAdmin($this->user)
        ->get('/admin/products')
        ->assertOk();

    $component = Livewire::test(ProductsIndex::class);

    expect($component->instance()->products()->count())->toBe(15);
    expect($component->instance()->products()->total())->toBe(25);
    expect($component->instance()->products()->hasMorePages())->toBeTrue();
});

it('creates a product via admin form', function () {
    actingAsAdmin($this->user);

    Livewire::test(ProductForm::class)
        ->set('title', 'Admin Created Tee')
        ->set('descriptionHtml', '<p>Soft cotton tee.</p>')
        ->set('variants.0.price', '19.99')
        ->set('variants.0.quantity', 7)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', [
        'store_id' => $this->store->getKey(),
        'title' => 'Admin Created Tee',
        'description_html' => '<p>Soft cotton tee.</p>',
    ]);

    $product = Product::query()->where('title', 'Admin Created Tee')->firstOrFail();

    expect($product->variants)->toHaveCount(1);
    expect($product->variants->first()->price_amount)->toBe(1999);
    expect($product->variants->first()->inventoryItem->quantity_on_hand)->toBe(7);
});

it('edits a product via admin form', function () {
    $product = app(ProductService::class)->create($this->store, ['title' => 'Original Title']);

    actingAsAdmin($this->user);

    Livewire::test(ProductForm::class, ['productId' => $product->getKey()])
        ->set('title', 'Renamed Title')
        ->set('vendor', 'Acme')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'title' => 'Renamed Title',
        'vendor' => 'Acme',
    ]);
});

it('bulk archives selected products', function () {
    $products = Product::factory()->count(3)->for($this->store)->create(['status' => ProductStatus::Draft]);

    actingAsAdmin($this->user);

    Livewire::test(ProductsIndex::class)
        ->set('selectedIds', $products->pluck('id')->all())
        ->call('bulkArchive')
        ->assertDispatched('toast');

    foreach ($products as $product) {
        expect($product->refresh()->status)->toBe(ProductStatus::Archived);
    }
});

it('uploads media from the product form', function () {
    Storage::fake('public');
    Queue::fake();

    $product = app(ProductService::class)->create($this->store, ['title' => 'Photogenic Mug']);

    actingAsAdmin($this->user);

    Livewire::test(ProductForm::class, ['productId' => $product->getKey()])
        ->set('newMedia', [UploadedFile::fake()->image('photo.jpg', 600, 400)])
        ->assertHasNoErrors();

    $this->assertDatabaseHas('product_media', [
        'product_id' => $product->getKey(),
        'type' => 'image',
    ]);
});

it('manages variants from the product form', function () {
    actingAsAdmin($this->user);

    Livewire::test(ProductForm::class)
        ->set('title', 'Sized Tee')
        ->call('addOption')
        ->set('options.0.name', 'Size')
        ->set('options.0.values', 'S, M')
        ->set('variants.0.price', '10.00')
        ->set('variants.1.price', '12.00')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('title', 'Sized Tee')->firstOrFail();

    expect($product->options)->toHaveCount(1);
    expect($product->options->first()->values)->toHaveCount(2);
    expect($product->variants)->toHaveCount(2);

    $labels = $product->variants
        ->map(fn ($variant) => $variant->optionValues->pluck('value')->implode(' / '))
        ->sort()
        ->values()
        ->all();

    expect($labels)->toBe(['M', 'S']);
});

it('restricts product management to authorized roles', function () {
    $support = createStoreMember($this->store, StoreUserRole::Support);

    actingAsAdmin($support)
        ->get('/admin/products')
        ->assertOk();

    actingAsAdmin($support)
        ->get('/admin/products/create')
        ->assertForbidden();
});

it('staff can create but not delete products', function () {
    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff);

    Livewire::test(ProductForm::class)
        ->set('title', 'Staff Product')
        ->set('variants.0.price', '5.00')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', ['title' => 'Staff Product']);

    $product = Product::query()->where('title', 'Staff Product')->firstOrFail();

    Livewire::test(ProductForm::class, ['productId' => $product->getKey()])
        ->call('deleteProduct')
        ->assertForbidden();

    expect($product->refresh()->status)->not->toBe(ProductStatus::Archived);
});
