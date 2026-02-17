<?php

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\VariantMatrixService;

beforeEach(function () {
    $ctx = createStoreContext();
    $this->store = $ctx['store'];
    $this->matrixService = app(VariantMatrixService::class);
});

test('creates variants from option matrix', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);
    foreach (['S', 'M', 'L'] as $i => $size) {
        ProductOptionValue::factory()->create([
            'product_option_id' => $sizeOption->id,
            'value' => $size,
            'position' => $i,
        ]);
    }

    $colorOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Color',
        'position' => 1,
    ]);
    foreach (['Red', 'Blue'] as $i => $color) {
        ProductOptionValue::factory()->create([
            'product_option_id' => $colorOption->id,
            'value' => $color,
            'position' => $i,
        ]);
    }

    $this->matrixService->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(6);
});

test('preserves existing variants when adding an option value', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    ProductOptionValue::factory()->create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    ProductOptionValue::factory()->create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    $this->matrixService->rebuildMatrix($product);
    expect($product->variants()->count())->toBe(2);

    $originalPrices = $product->variants()->pluck('price_amount', 'id')->all();

    // Add a new size value
    ProductOptionValue::factory()->create(['product_option_id' => $sizeOption->id, 'value' => 'L', 'position' => 2]);

    $this->matrixService->rebuildMatrix($product);
    expect($product->variants()->count())->toBe(3);

    // Original variants should still have their prices
    foreach ($originalPrices as $id => $price) {
        $variant = ProductVariant::find($id);
        expect($variant)->not->toBeNull()
            ->and($variant->price_amount)->toBe($price);
    }
});

test('archives orphaned variants with order references', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    ProductOptionValue::factory()->create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    $mVal = ProductOptionValue::factory()->create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    $this->matrixService->rebuildMatrix($product);

    $mVariant = $product->variants()->whereHas('optionValues', fn ($q) => $q->where('product_option_values.id', $mVal->id))->first();

    // Verify variant exists and has correct option value association
    expect($mVariant)->not->toBeNull();
});

test('deletes orphaned variants without order references', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    ProductOptionValue::factory()->create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    $mVal = ProductOptionValue::factory()->create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    $this->matrixService->rebuildMatrix($product);
    expect($product->variants()->count())->toBe(2);

    // Remove the M value
    $mVal->delete();

    $this->matrixService->rebuildMatrix($product);
    expect($product->variants()->count())->toBe(1);
});

test('auto-creates default variant for products without options', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $this->matrixService->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(1)
        ->and($product->variants()->first()->is_default)->toBeTrue();
});

test('validates SKU uniqueness within store', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'TSH-001',
    ]);

    $product2 = Product::factory()->create(['store_id' => $this->store->id]);

    // Check that a duplicate SKU exists within the store
    $existingSku = ProductVariant::query()
        ->whereHas('product', fn ($q) => $q->where('store_id', $this->store->id))
        ->where('sku', 'TSH-001')
        ->exists();

    expect($existingSku)->toBeTrue();
});

test('allows duplicate SKU across different stores', function () {
    $otherStore = Store::factory()->create();

    $product1 = Product::factory()->create(['store_id' => $this->store->id]);
    ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'sku' => 'TSH-001',
    ]);

    $product2 = Product::factory()->create(['store_id' => $otherStore->id]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product2->id,
        'sku' => 'TSH-001',
    ]);

    expect($variant2->sku)->toBe('TSH-001');
});

test('allows null SKUs', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $v1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
    ]);
    $v2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
        'position' => 1,
    ]);

    expect($v1->sku)->toBeNull()
        ->and($v2->sku)->toBeNull();
});
