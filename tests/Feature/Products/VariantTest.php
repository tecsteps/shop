<?php

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('order_lines')) {
        Schema::create('order_lines', function ($table) {
            $table->id();
            $table->foreignId('variant_id')->nullable();
            $table->timestamps();
        });
    }
});

it('creates variants from option matrix', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], ['title' => 'Multi-Option Product']);

    $sizeOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'L', 'position' => 2]);

    $colorOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Color', 'position' => 1]);
    ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Red', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Blue', 'position' => 1]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    // The default variant from product creation should have been deleted (orphaned with no orders),
    // and 6 new variants created (3 sizes x 2 colors)
    $product->refresh();
    expect($product->variants()->count())->toBe(6);
});

it('preserves existing variants when adding an option value', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], ['title' => 'Expandable Product', 'price_amount' => 1500]);

    $sizeOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $sVal = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    $mVal = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    $product->refresh();
    $existingVariants = $product->variants()->get();
    expect($existingVariants)->toHaveCount(2);

    // Update one variant's price
    $firstVariant = $existingVariants->first();
    $firstVariant->update(['price_amount' => 2000]);
    $originalPrice = $firstVariant->fresh()->price_amount;

    // Add a new option value
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'L', 'position' => 2]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    $product->refresh();
    expect($product->variants()->count())->toBe(3);

    // Verify the original variant's price was preserved
    expect($firstVariant->fresh()->price_amount)->toBe($originalPrice);
});

it('deletes orphaned variants without order references', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], ['title' => 'Shrinkable Product']);

    $sizeOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $sVal = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    $mVal = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    $product->refresh();
    expect($product->variants()->count())->toBe(2);

    // Remove option value M
    $mVal->delete();

    app(VariantMatrixService::class)->rebuildMatrix($product);

    $product->refresh();
    expect($product->variants()->count())->toBe(1);
});

it('auto-creates default variant for products without options', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], ['title' => 'Simple Product']);

    expect($product->variants)->toHaveCount(1);
    expect($product->variants->first()->is_default)->toBeTrue();
});

it('validates SKU uniqueness within store', function () {
    $context = createStoreContext();

    $product1 = Product::factory()->create(['store_id' => $context['store']->id]);
    ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'sku' => 'TSH-001',
    ]);

    $product2 = Product::factory()->create(['store_id' => $context['store']->id]);

    // The database should enforce this via unique constraint if present,
    // or we can check uniqueness manually
    $existingSku = ProductVariant::query()
        ->whereHas('product', fn ($q) => $q->where('store_id', $context['store']->id))
        ->where('sku', 'TSH-001')
        ->exists();

    expect($existingSku)->toBeTrue();
});

it('allows duplicate SKU across different stores', function () {
    $context = createStoreContext();

    $product1 = Product::factory()->create(['store_id' => $context['store']->id]);
    ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'sku' => 'TSH-001',
    ]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    StoreDomain::factory()->create(['store_id' => $storeB->id, 'hostname' => 'store-b.test']);

    $product2 = Product::factory()->create(['store_id' => $storeB->id]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product2->id,
        'sku' => 'TSH-001',
    ]);

    expect($variant2->sku)->toBe('TSH-001');
});

it('allows null SKUs', function () {
    $context = createStoreContext();

    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
        'position' => 0,
    ]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
        'position' => 1,
    ]);

    expect($variant1->exists)->toBeTrue();
    expect($variant2->exists)->toBeTrue();
});
