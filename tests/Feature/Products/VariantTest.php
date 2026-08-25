<?php

use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Services\VariantMatrixService;

it('creates variants from option matrix', function () {
    $ctx = createStoreContext();
    $product = Product::factory()->create(['store_id' => $ctx['store']->id]);
    $size = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $color = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Color', 'position' => 1]);
    foreach (['S', 'M', 'L'] as $i => $v) {
        ProductOptionValue::factory()->create(['product_option_id' => $size->id, 'value' => $v, 'position' => $i]);
    }
    foreach (['Red', 'Blue'] as $i => $v) {
        ProductOptionValue::factory()->create(['product_option_id' => $color->id, 'value' => $v, 'position' => $i]);
    }

    app(VariantMatrixService::class)->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(6);
});

it('auto-creates default variant for products without options', function () {
    $ctx = createStoreContext();
    $product = Product::factory()->create(['store_id' => $ctx['store']->id]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(1);
    expect($product->variants()->first()->is_default)->toBeTrue();
});

it('deletes orphaned variants without order references', function () {
    $ctx = createStoreContext();
    $product = Product::factory()->create(['store_id' => $ctx['store']->id]);
    $size = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $small = ProductOptionValue::factory()->create(['product_option_id' => $size->id, 'value' => 'S', 'position' => 0]);
    $large = ProductOptionValue::factory()->create(['product_option_id' => $size->id, 'value' => 'L', 'position' => 1]);
    app(VariantMatrixService::class)->rebuildMatrix($product);
    expect($product->variants()->count())->toBe(2);

    $large->delete();
    app(VariantMatrixService::class)->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(1);
    expect($product->variants()->first()->optionValues->first()->value)->toBe('S');
});

it('archives orphaned variants with order references', function () {
    $ctx = createStoreContext();
    $product = Product::factory()->create(['store_id' => $ctx['store']->id]);
    $size = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $small = ProductOptionValue::factory()->create(['product_option_id' => $size->id, 'value' => 'S', 'position' => 0]);
    $large = ProductOptionValue::factory()->create(['product_option_id' => $size->id, 'value' => 'L', 'position' => 1]);
    app(VariantMatrixService::class)->rebuildMatrix($product);
    $largeVariant = $product->variants()->whereHas('optionValues', fn ($q) => $q->where('value', 'L'))->first();
    OrderLine::factory()->create(['variant_id' => $largeVariant->id]);

    $large->delete();
    app(VariantMatrixService::class)->rebuildMatrix($product);

    expect($largeVariant->fresh()->status)->toBe('archived');
});
