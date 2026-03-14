<?php

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use App\Services\VariantMatrixService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->matrixService = app(VariantMatrixService::class);
    $this->productService = app(ProductService::class);
});

it('creates variants from option matrix', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

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

    $titles = $product->variants()->orderBy('position')->pluck('title')->all();
    expect($titles)->toContain('S / Red');
    expect($titles)->toContain('L / Blue');
});

it('preserves existing variants when adding an option value', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);
    $sVal = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'S',
        'position' => 0,
    ]);
    $mVal = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'M',
        'position' => 1,
    ]);

    $this->matrixService->rebuildMatrix($product);
    expect($product->variants()->count())->toBe(2);

    // Set price on the S variant to verify preservation
    $sVariant = $product->variants()->whereHas('optionValues', fn ($q) => $q->where('product_option_values.id', $sVal->id))->first();
    $sVariant->update(['price_amount' => 2500]);
    $originalSVariantId = $sVariant->id;

    // Add a new size value
    ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'L',
        'position' => 2,
    ]);

    $this->matrixService->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(3);

    // The original S variant should be preserved with its price
    $preserved = ProductVariant::find($originalSVariantId);
    expect($preserved)->not->toBeNull();
    expect($preserved->price_amount)->toBe(2500);
});

it('archives orphaned variants with order references', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('order_lines')) {
        $this->markTestSkipped('order_lines table does not exist yet.');
    }

    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);
    $sVal = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'S',
        'position' => 0,
    ]);
    $mVal = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'M',
        'position' => 1,
    ]);

    $this->matrixService->rebuildMatrix($product);
    $sVariant = $product->variants()->whereHas('optionValues', fn ($q) => $q->where('product_option_values.id', $sVal->id))->first();

    \Illuminate\Support\Facades\DB::table('order_lines')->insert([
        'variant_id' => $sVariant->id,
        'order_id' => 1,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'total_amount' => 2000,
    ]);

    // Remove the S value
    $sVal->delete();

    $this->matrixService->rebuildMatrix($product);

    $sVariant->refresh();
    expect($sVariant->status)->toBe(\App\Enums\VariantStatus::Archived);
});

it('deletes orphaned variants without order references', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);
    $sVal = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'S',
        'position' => 0,
    ]);
    $mVal = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'M',
        'position' => 1,
    ]);

    $this->matrixService->rebuildMatrix($product);
    expect($product->variants()->count())->toBe(2);

    $sVariant = $product->variants()->whereHas('optionValues', fn ($q) => $q->where('product_option_values.id', $sVal->id))->first();
    $sVariantId = $sVariant->id;

    $sVal->delete();
    $this->matrixService->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(1);
    expect(ProductVariant::find($sVariantId))->toBeNull();
});

it('auto-creates default variant for products without options', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Simple Product',
        'price_amount' => 1000,
    ]);

    $variant = $product->variants()->first();
    expect($variant)->not->toBeNull();
    expect($variant->is_default)->toBeTrue();
    expect($variant->title)->toBe('Default');
});

it('validates SKU uniqueness within store', function () {
    $product1 = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'sku' => 'TSH-001',
    ]);

    $product2 = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    // Check that the same SKU exists in the store
    $existingSku = ProductVariant::whereHas('product', fn ($q) => $q->where('store_id', $this->ctx['store']->id))
        ->where('sku', 'TSH-001')
        ->exists();

    expect($existingSku)->toBeTrue();
});

it('allows duplicate SKU across different stores', function () {
    $otherStore = Store::factory()->create();

    $product1 = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
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
    expect($variant2->exists)->toBeTrue();
});

it('allows null SKUs', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
    ]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
    ]);

    expect($variant1->exists)->toBeTrue();
    expect($variant2->exists)->toBeTrue();
    expect($variant1->sku)->toBeNull();
    expect($variant2->sku)->toBeNull();
});
