<?php

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\VariantMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('variant matrix rebuild creates missing cartesian combinations and inventory', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::factory()->create(['store_id' => $store->getKey()]);

    $size = ProductOption::factory()->create(['product_id' => $product->getKey(), 'name' => 'Size', 'position' => 0]);
    $small = ProductOptionValue::factory()->create(['product_option_id' => $size->getKey(), 'value' => 'S', 'position' => 0]);
    $medium = ProductOptionValue::factory()->create(['product_option_id' => $size->getKey(), 'value' => 'M', 'position' => 1]);

    $color = ProductOption::factory()->create(['product_id' => $product->getKey(), 'name' => 'Color', 'position' => 1]);
    $black = ProductOptionValue::factory()->create(['product_option_id' => $color->getKey(), 'value' => 'Black', 'position' => 0]);
    $white = ProductOptionValue::factory()->create(['product_option_id' => $color->getKey(), 'value' => 'White', 'position' => 1]);

    $variant = ProductVariant::factory()->default()->create([
        'product_id' => $product->getKey(),
        'price_amount' => 2500,
    ]);
    $variant->optionValues()->sync([$small->getKey(), $black->getKey()]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(4)
        ->and($product->variants()->whereHas('optionValues', fn ($query) => $query->whereKey($medium->getKey()))->count())->toBe(2)
        ->and($product->variants()->whereHas('optionValues', fn ($query) => $query->whereKey($white->getKey()))->count())->toBe(2)
        ->and($product->variants()->whereHas('inventoryItem')->count())->toBe(4);
});

test('variant matrix rebuild creates a default variant when there are no options', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::factory()->create(['store_id' => $store->getKey()]);
    $product->variants()->delete();

    app(VariantMatrixService::class)->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(1)
        ->and($product->variants()->first()->is_default)->toBeTrue();
});

test('variant matrix rebuild collapses to one default variant after options are removed', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::factory()->create(['store_id' => $store->getKey()]);
    ProductVariant::factory()->count(3)->for($product)->create();

    app(VariantMatrixService::class)->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(1)
        ->and($product->variants()->first()->is_default)->toBeTrue()
        ->and($product->variants()->first()->inventoryItem)->not->toBeNull();
});
