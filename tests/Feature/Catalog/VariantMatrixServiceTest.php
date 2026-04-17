<?php

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\Store;
use App\Services\ProductService;
use App\Services\VariantMatrixService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('rebuilds the variant matrix as the cartesian product of option values', function () {
    $store = Store::factory()->create();
    $product = app(ProductService::class)->create((int) $store->getKey(), [
        'title' => 'Matrix Tee',
        'price_amount' => 1500,
    ]);

    $sizeOption = ProductOption::query()->create([
        'product_id' => $product->getKey(),
        'name' => 'Size',
        'position' => 0,
    ]);

    $small = ProductOptionValue::query()->create([
        'product_option_id' => $sizeOption->getKey(),
        'value' => 'Small',
        'position' => 0,
    ]);
    $medium = ProductOptionValue::query()->create([
        'product_option_id' => $sizeOption->getKey(),
        'value' => 'Medium',
        'position' => 1,
    ]);

    $colorOption = ProductOption::query()->create([
        'product_id' => $product->getKey(),
        'name' => 'Color',
        'position' => 1,
    ]);

    $red = ProductOptionValue::query()->create([
        'product_option_id' => $colorOption->getKey(),
        'value' => 'Red',
        'position' => 0,
    ]);
    $blue = ProductOptionValue::query()->create([
        'product_option_id' => $colorOption->getKey(),
        'value' => 'Blue',
        'position' => 1,
    ]);

    app(VariantMatrixService::class)->rebuildMatrix($product->fresh());

    $product = $product->fresh();
    $product->load('variants.optionValues');

    $matrixVariants = $product->variants()->whereHas('optionValues')->get();

    expect($matrixVariants)->toHaveCount(4);

    $sets = $matrixVariants->map(function ($variant) {
        return $variant->optionValues->pluck('id')->sort()->values()->all();
    })->values();

    $expected = [
        [$small->getKey(), $red->getKey()],
        [$small->getKey(), $blue->getKey()],
        [$medium->getKey(), $red->getKey()],
        [$medium->getKey(), $blue->getKey()],
    ];

    foreach ($expected as $combo) {
        sort($combo);
        expect($sets->contains(fn ($actual) => $actual === $combo))->toBeTrue();
    }
});

it('deletes orphan variants without order references', function () {
    $store = Store::factory()->create();
    $service = app(VariantMatrixService::class);
    $product = app(ProductService::class)->create((int) $store->getKey(), [
        'title' => 'Shrinking',
        'price_amount' => 500,
    ]);

    $sizeOption = ProductOption::query()->create([
        'product_id' => $product->getKey(),
        'name' => 'Size',
        'position' => 0,
    ]);

    $small = ProductOptionValue::query()->create([
        'product_option_id' => $sizeOption->getKey(),
        'value' => 'S',
        'position' => 0,
    ]);
    $medium = ProductOptionValue::query()->create([
        'product_option_id' => $sizeOption->getKey(),
        'value' => 'M',
        'position' => 1,
    ]);

    $service->rebuildMatrix($product->fresh());
    expect($product->fresh()->variants()->whereHas('optionValues')->count())->toBe(2);

    $medium->delete();

    $service->rebuildMatrix($product->fresh());

    expect($product->fresh()->variants()->whereHas('optionValues')->count())->toBe(1);
});
