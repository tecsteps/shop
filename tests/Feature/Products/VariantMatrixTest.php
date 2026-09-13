<?php

use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use Illuminate\Validation\ValidationException;
use Tests\Support\CatalogTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates variants from the option matrix', function () {
    ['store' => $store] = $this->createStoreContext();

    $product = app(ProductService::class)->create($store, [
        'title' => 'Matrix Tee',
        'options' => [
            ['name' => 'Size', 'position' => 1, 'values' => ['S', 'M', 'L']],
            ['name' => 'Color', 'position' => 2, 'values' => ['Red', 'Blue']],
        ],
    ]);

    expect($product->productVariants()->count())->toBe(6)
        ->and(InventoryItem::query()->where('store_id', $store->id)->count())->toBe(6)
        ->and($product->productVariants()->where('is_default', true)->count())->toBe(1);

    $combos = $product->productVariants()->with('optionValues')->get()
        ->map(fn ($variant) => $variant->optionValues->pluck('value')->sort()->values()->all())
        ->all();

    expect($combos)->toHaveCount(6);
    foreach ($combos as $combo) {
        expect($combo)->toHaveCount(2);
    }
});

it('preserves existing variants when adding an option value', function () {
    ['store' => $store] = $this->createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($store, [
        'title' => 'Growing Tee',
        'options' => [
            ['name' => 'Size', 'position' => 1, 'values' => ['S', 'M']],
        ],
        'variants' => [
            ['sku' => 'G-S', 'price_amount' => 1000, 'is_default' => true, 'option_values' => [['option_name' => 'Size', 'value' => 'S']]],
            ['sku' => 'G-M', 'price_amount' => 2000, 'option_values' => [['option_name' => 'Size', 'value' => 'M']]],
        ],
    ]);

    $service->update($product->fresh(), [
        'options' => [
            ['name' => 'Size', 'position' => 1, 'values' => ['S', 'M', 'L']],
        ],
    ]);

    $fresh = $product->fresh();
    expect($fresh->productVariants()->count())->toBe(3);
    expect($fresh->productVariants()->where('sku', 'G-S')->first()->price_amount)->toBe(1000);
    expect($fresh->productVariants()->where('sku', 'G-M')->first()->price_amount)->toBe(2000);

    $new = $fresh->productVariants()->whereNull('sku')->first();
    expect($new)->not->toBeNull();
    expect($new->optionValues->pluck('value')->all())->toBe(['L']);
});

it('archives orphaned variants with order references', function () {
    ['store' => $store] = $this->createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($store, [
        'title' => 'Ordered Tee',
        'options' => [['name' => 'Size', 'position' => 1, 'values' => ['S', 'M']]],
        'variants' => [
            ['sku' => 'O-S', 'price_amount' => 1000, 'is_default' => true, 'option_values' => [['option_name' => 'Size', 'value' => 'S']]],
            ['sku' => 'O-M', 'price_amount' => 1000, 'option_values' => [['option_name' => 'Size', 'value' => 'M']]],
        ],
    ]);

    $orderedVariant = $product->productVariants()->where('sku', 'O-S')->first();
    OrderLine::factory()->create(['product_id' => $product->id, 'variant_id' => $orderedVariant->id]);

    $service->update($product->fresh(), [
        'options' => [['name' => 'Size', 'position' => 1, 'values' => ['M']]],
    ]);

    $orphan = $orderedVariant->fresh();
    expect($orphan)->not->toBeNull();
    expect($orphan->status)->toBe(VariantStatus::Archived);
    expect($product->fresh()->productVariants()->where('sku', 'O-M')->exists())->toBeTrue();
});

it('deletes orphaned variants without order references', function () {
    ['store' => $store] = $this->createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($store, [
        'title' => 'Shrinking Tee',
        'options' => [['name' => 'Size', 'position' => 1, 'values' => ['S', 'M', 'L']]],
    ]);

    expect($product->productVariants()->count())->toBe(3);

    $service->update($product->fresh(), [
        'options' => [['name' => 'Size', 'position' => 1, 'values' => ['S']]],
    ]);

    $fresh = $product->fresh();
    expect($fresh->productVariants()->count())->toBe(1);
    expect($fresh->productVariants()->first()->optionValues->pluck('value')->all())->toBe(['S']);
});

it('auto-creates a default variant for products without options', function () {
    ['store' => $store] = $this->createStoreContext();

    $product = app(ProductService::class)->create($store, ['title' => 'Simple Mug']);

    $variants = $product->productVariants;
    expect($variants)->toHaveCount(1);
    expect($variants->first()->is_default)->toBeTrue();
    expect(InventoryItem::query()->where('variant_id', $variants->first()->id)->exists())->toBeTrue();
});

it('validates sku uniqueness within the store', function () {
    ['store' => $store] = $this->createStoreContext();
    $service = app(ProductService::class);

    $service->create($store, CatalogTestHelper::productPayload());

    expect(fn () => $service->create($store, CatalogTestHelper::productPayload(['title' => 'Dup SKU'])))
        ->toThrow(ValidationException::class);
});

it('allows duplicate skus across different stores', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    $service = app(ProductService::class);

    $service->create($storeA, CatalogTestHelper::productPayload());
    $productB = $service->create($storeB, CatalogTestHelper::productPayload());

    expect($productB->productVariants()->where('sku', 'TSH-S')->exists())->toBeTrue();
});

it('allows null skus', function () {
    ['store' => $store] = $this->createStoreContext();

    $product = app(ProductService::class)->create($store, [
        'title' => 'SKU-less Tee',
        'options' => [['name' => 'Size', 'position' => 1, 'values' => ['S', 'M']]],
        'variants' => [
            ['price_amount' => 1000, 'is_default' => true, 'option_values' => [['option_name' => 'Size', 'value' => 'S']]],
            ['sku' => '', 'price_amount' => 1000, 'option_values' => [['option_name' => 'Size', 'value' => 'M']]],
        ],
    ]);

    expect($product->productVariants()->whereNull('sku')->count())->toBe(2);
});

it('rejects duplicate skus within the same payload', function () {
    ['store' => $store] = $this->createStoreContext();

    expect(fn () => app(ProductService::class)->create($store, [
        'title' => 'Dup Payload Tee',
        'options' => [['name' => 'Size', 'position' => 1, 'values' => ['S', 'M']]],
        'variants' => [
            ['sku' => 'SAME', 'price_amount' => 1000, 'option_values' => [['option_name' => 'Size', 'value' => 'S']]],
            ['sku' => 'SAME', 'price_amount' => 1000, 'option_values' => [['option_name' => 'Size', 'value' => 'M']]],
        ],
    ]))->toThrow(ValidationException::class);
});

it('rebuilds the matrix directly and keeps a single default', function () {
    ['store' => $store] = $this->createStoreContext();

    $product = Product::factory()->create(['store_id' => $store->id]);
    $option = $product->productOptions()->create(['name' => 'Size', 'position' => 1]);
    $option->productOptionValues()->create(['value' => 'S', 'position' => 1]);
    $option->productOptionValues()->create(['value' => 'M', 'position' => 2]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    $fresh = $product->fresh();
    expect($fresh->productVariants()->count())->toBe(2);
    expect($fresh->productVariants()->where('is_default', true)->count())->toBe(1);
});

it('rejects variants referencing undefined options', function () {
    ['store' => $store] = $this->createStoreContext();

    expect(fn () => app(ProductService::class)->create($store, [
        'title' => 'Bad Option Tee',
        'options' => [['name' => 'Size', 'position' => 1]],
        'variants' => [
            ['sku' => 'BAD-1', 'price_amount' => 1000, 'option_values' => [['option_name' => 'Color', 'value' => 'Red']]],
        ],
    ]))->toThrow(ValidationException::class);
});
