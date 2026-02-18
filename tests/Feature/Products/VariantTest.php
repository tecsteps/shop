<?php

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->matrixService = new VariantMatrixService;
    $this->productService = new ProductService(new HandleGenerator);
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

    $activeVariants = $product->variants()->where('status', VariantStatus::Active)->count();
    expect($activeVariants)->toBe(6);
});

it('preserves existing variants when adding an option value', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    $small = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'S',
        'position' => 0,
    ]);
    $medium = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'M',
        'position' => 1,
    ]);

    $this->matrixService->rebuildMatrix($product);
    expect($product->variants()->where('status', VariantStatus::Active)->count())->toBe(2);

    $existingVariant = $product->variants()->first();
    $originalPrice = $existingVariant->price_amount;

    ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'L',
        'position' => 2,
    ]);

    $this->matrixService->rebuildMatrix($product);

    expect($product->variants()->where('status', VariantStatus::Active)->count())->toBe(3)
        ->and($existingVariant->fresh()->price_amount)->toBe($originalPrice);
});

it('archives orphaned variants when option values are removed', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    $small = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'S',
        'position' => 0,
    ]);
    $medium = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'M',
        'position' => 1,
    ]);

    $this->matrixService->rebuildMatrix($product);
    expect($product->variants()->where('status', VariantStatus::Active)->count())->toBe(2);

    $medium->delete();
    $this->matrixService->rebuildMatrix($product);

    expect($product->variants()->where('status', VariantStatus::Active)->count())->toBe(1)
        ->and($product->variants()->where('status', VariantStatus::Archived)->count())->toBeGreaterThanOrEqual(1);
});

it('auto-creates default variant for products without options', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Simple Product',
        'price_amount' => 1500,
    ]);

    expect($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue();
});

it('allows null SKUs on multiple variants', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
    ]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
        'position' => 1,
    ]);

    expect($variant1->exists)->toBeTrue()
        ->and($variant2->exists)->toBeTrue();
});

it('stores option values on variants via pivot', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    $small = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'S',
        'position' => 0,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    $variant->optionValues()->attach($small->id);

    expect($variant->optionValues)->toHaveCount(1)
        ->and($variant->optionValues->first()->value)->toBe('S');
});

it('sets variant positions correctly', function () {
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

    $this->matrixService->rebuildMatrix($product);

    $positions = $product->variants()->orderBy('position')->pluck('position')->all();
    expect($positions)->toHaveCount(3)
        ->and(array_unique($positions))->toHaveCount(3);
});

it('uses default variant price for generated variants', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    ProductVariant::factory()->default()->create([
        'product_id' => $product->id,
        'price_amount' => 3500,
    ]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    foreach (['S', 'M'] as $i => $size) {
        ProductOptionValue::factory()->create([
            'product_option_id' => $sizeOption->id,
            'value' => $size,
            'position' => $i,
        ]);
    }

    $this->matrixService->rebuildMatrix($product);

    $newVariants = $product->variants()
        ->where('is_default', false)
        ->where('status', VariantStatus::Active)
        ->get();

    foreach ($newVariants as $variant) {
        expect($variant->price_amount)->toBe(3500);
    }
});
