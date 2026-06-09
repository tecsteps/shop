<?php

use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use Illuminate\Validation\ValidationException;

it('creates variants from option matrix', function () {
    $context = createStoreContext();

    $product = app(ProductService::class)->create($context['store'], [
        'title' => 'Matrix Tee',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M', 'L']],
            ['name' => 'Color', 'values' => ['Red', 'Blue']],
        ],
    ]);

    expect($product->variants)->toHaveCount(6);

    foreach ($product->variants as $variant) {
        expect($variant->optionValues)->toHaveCount(2);
        expect($variant->inventoryItem)->not->toBeNull();
    }
});

it('preserves existing variants when adding an option value', function () {
    $context = createStoreContext();

    $product = app(ProductService::class)->create($context['store'], [
        'title' => 'Growing Tee',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M']],
        ],
    ]);

    $originalVariants = $product->variants;
    expect($originalVariants)->toHaveCount(2);

    $originalVariants[0]->update(['price_amount' => 1100]);
    $originalVariants[1]->update(['price_amount' => 1200]);

    $product->options->first()->values()->create(['value' => 'L', 'position' => 2]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    $product->refresh()->load('variants');

    expect($product->variants)->toHaveCount(3);
    expect($product->variants->find($originalVariants[0]->getKey())->price_amount)->toBe(1100);
    expect($product->variants->find($originalVariants[1]->getKey())->price_amount)->toBe(1200);
});

it('archives orphaned variants with order references')
    ->todo('Phase 5: order_lines table does not exist yet; VariantMatrixService already archives referenced orphans once it does');

it('deletes orphaned variants without order references', function () {
    $context = createStoreContext();

    $product = app(ProductService::class)->create($context['store'], [
        'title' => 'Shrinking Tee',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M', 'L']],
        ],
    ]);

    expect($product->variants)->toHaveCount(3);

    $removedValue = $product->options->first()->values->firstWhere('value', 'L');
    $orphanedVariantId = $product->variants
        ->first(fn ($variant) => $variant->optionValues->contains('id', $removedValue->getKey()))
        ->getKey();

    $removedValue->delete();

    app(VariantMatrixService::class)->rebuildMatrix($product);

    $this->assertDatabaseMissing('product_variants', ['id' => $orphanedVariantId]);
    expect($product->refresh()->variants)->toHaveCount(2);
});

it('auto-creates default variant for products without options', function () {
    $context = createStoreContext();

    $product = app(ProductService::class)->create($context['store'], [
        'title' => 'Optionless Product',
    ]);

    expect($product->variants)->toHaveCount(1);
    expect($product->variants->first()->is_default)->toBeTrue();
});

it('validates SKU uniqueness within store', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $productA = $service->create($context['store'], ['title' => 'Product A']);
    $productB = $service->create($context['store'], ['title' => 'Product B']);

    $service->createVariant($productA, ['sku' => 'TSH-001']);

    expect(fn () => $service->createVariant($productB, ['sku' => 'TSH-001']))
        ->toThrow(ValidationException::class);
});

it('allows duplicate SKU across different stores', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    $service = app(ProductService::class);

    $productA = Product::factory()->for($storeA)->create();
    $productB = Product::factory()->for($storeB)->create();

    $variantA = $service->createVariant($productA, ['sku' => 'TSH-001']);
    $variantB = $service->createVariant($productB, ['sku' => 'TSH-001']);

    expect($variantA->sku)->toBe('TSH-001');
    expect($variantB->sku)->toBe('TSH-001');
});

it('allows null SKUs', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = Product::factory()->for($context['store'])->create();

    $first = $service->createVariant($product, ['sku' => null]);
    $second = $service->createVariant($product, ['sku' => null]);

    expect($first->exists)->toBeTrue();
    expect($second->exists)->toBeTrue();
    expect($product->variants()->count())->toBe(2);
});
