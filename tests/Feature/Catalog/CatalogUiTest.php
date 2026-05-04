<?php

use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductIndex;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\ProductService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

test('storefront catalog browsing routes render seeded products', function () {
    $this->withHeader('Host', 'shop.test')
        ->get('/')
        ->assertSuccessful()
        ->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99 EUR');

    $this->withHeader('Host', 'shop.test')
        ->get('/collections/t-shirts')
        ->assertSuccessful()
        ->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Unreleased Winter Jacket');

    $this->withHeader('Host', 'shop.test')
        ->get('/products/classic-cotton-t-shirt')
        ->assertSuccessful()
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Size')
        ->assertSee('Color')
        ->assertSee('Add to cart');

    $this->withHeader('Host', 'shop.test')
        ->get('/search?q=draft')
        ->assertSuccessful()
        ->assertDontSee('Unreleased Winter Jacket');
});

test('admin catalog routes require authentication and render for store admins', function () {
    $this->get('/admin/products')->assertRedirect('/admin/login');

    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/products')
        ->assertSuccessful()
        ->assertSee('Products')
        ->assertSee('Add product');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/collections')
        ->assertSuccessful()
        ->assertSee('Collections')
        ->assertSee('T-Shirts');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/inventory')
        ->assertSuccessful()
        ->assertSee('Inventory')
        ->assertSee('Limited Edition Sneakers');
});

test('admin product form creates edits archives and index filters products', function () {
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();
    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('title', 'Test Product Created by E2E')
        ->set('handle', 'test-product-created-by-e2e')
        ->set('descriptionHtml', 'This product was created by the E2E test suite.')
        ->set('vendor', 'Test Vendor')
        ->set('productType', 'T-Shirts')
        ->set('variants.0.price', '29.99')
        ->set('variants.0.sku', 'E2E-TEST-001')
        ->set('variants.0.quantity', 50)
        ->call('save')
        ->assertSee('Product saved');

    $product = Product::query()->where('handle', 'test-product-created-by-e2e')->firstOrFail();

    expect($product->variants()->first()?->price_amount)->toBe(2999)
        ->and($product->variants()->first()?->inventoryItem?->quantity_on_hand)->toBe(50);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product])
        ->set('title', 'Test Product Updated')
        ->call('save')
        ->assertSee('Product saved');

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product->refresh()])
        ->set('variants.0.sku', 'ACME-CTSH-S-WHT')
        ->call('save')
        ->assertHasErrors(['variants.0.sku']);

    expect($product->refresh()->variants()->first()?->sku)->toBe('E2E-TEST-001');

    $draftProduct = app(ProductService::class)->create($store, [
        'title' => 'Draft Product With Price Later',
        'handle' => 'draft-product-with-price-later',
        'status' => 'draft',
        'variants' => [[
            'sku' => 'DRAFT-PRICE-LATER-001',
            'price_amount' => 0,
            'currency' => $store->default_currency,
            'quantity_on_hand' => 4,
            'is_default' => true,
            'position' => 0,
        ]],
    ]);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $draftProduct])
        ->set('variants.0.price', '17.50')
        ->set('status', 'active')
        ->call('save')
        ->assertSee('Product saved');

    expect($draftProduct->refresh()->status->value)->toBe('active')
        ->and($draftProduct->variants()->first()?->price_amount)->toBe(1750);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product->refresh()])
        ->set('status', 'archived')
        ->call('save')
        ->assertSee('Product saved');

    expect($product->refresh()->status->value)->toBe('archived');

    Livewire::actingAs($user)
        ->test(ProductIndex::class)
        ->set('statusFilter', 'draft')
        ->assertSee('Unreleased Winter Jacket')
        ->assertDontSee('Classic Cotton T-Shirt')
        ->set('statusFilter', 'active')
        ->set('search', 'Cotton')
        ->assertSee('Classic Cotton T-Shirt');
});

test('admin product form generates and syncs option variant matrix', function () {
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();
    app()->instance('current_store', $store);

    $component = Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('title', 'Matrix Product')
        ->set('handle', 'matrix-product')
        ->set('options', [
            ['name' => 'Size', 'values' => 'S, M'],
            ['name' => 'Color', 'values' => 'Black, White'],
        ])
        ->call('generateVariants');

    $generatedVariants = $component->get('variants');

    expect($generatedVariants)->toHaveCount(4)
        ->and($generatedVariants[0]['label'])->toBe('S / Black')
        ->and($generatedVariants[3]['label'])->toBe('M / White');

    foreach (range(0, 3) as $index) {
        $component
            ->set("variants.{$index}.sku", 'MATRIX-'.$index)
            ->set("variants.{$index}.price", '19.99')
            ->set("variants.{$index}.quantity", 10 + $index);
    }

    $component
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Product saved');

    $product = Product::query()->where('handle', 'matrix-product')->firstOrFail();
    $labels = matrixVariantLabels($product);

    expect($product->variants)->toHaveCount(4)
        ->and($labels)->toBe(['M / Black', 'M / White', 'S / Black', 'S / White']);

    $editComponent = Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product->refresh()])
        ->set('options.1.values', 'Black, Navy')
        ->call('generateVariants');

    expect(collect($editComponent->get('variants'))->pluck('label')->sort()->values()->all())
        ->toBe(['M / Black', 'M / Navy', 'S / Black', 'S / Navy']);

    $editComponent
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Product saved');

    expect(matrixVariantLabels($product->refresh()))->toBe(['M / Black', 'M / Navy', 'S / Black', 'S / Navy']);
});

function matrixVariantLabels(Product $product): array
{
    return $product->variants()
        ->with(['optionValues.option'])
        ->get()
        ->map(fn ($variant): string => $variant->optionValues
            ->sortBy(fn ($value): int => $value->option->position)
            ->pluck('value')
            ->implode(' / '))
        ->sort()
        ->values()
        ->all();
}

test('admin collection form creates and assigns products', function () {
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();
    $product = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail();
    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(CollectionForm::class)
        ->set('title', 'E2E Test Collection')
        ->set('handle', 'e2e-test-collection')
        ->set('descriptionHtml', '<p>Created from tests.</p>')
        ->call('addProduct', $product->getKey())
        ->call('save')
        ->assertSee('Collection saved');

    $collection = Collection::query()->where('handle', 'e2e-test-collection')->firstOrFail();

    expect($collection->products()->pluck('products.id')->all())->toBe([$product->getKey()]);
});
