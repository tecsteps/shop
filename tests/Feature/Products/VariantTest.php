<?php

use App\Models\ProductVariant;
use App\Services\ProductService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->productService = app(ProductService::class);
});

it('creates variants from option matrix', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Matrix Product',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M', 'L']],
            ['name' => 'Color', 'values' => ['Red', 'Blue']],
        ],
        'variants' => array_fill(0, 6, ['price_amount' => 2500]),
    ]);

    expect($product->variants)->toHaveCount(6);
});

it('auto-creates default variant for products without options', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Simple Product',
        'price_amount' => 2500,
    ]);

    expect($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue();
});

it('creates inventory item when variant is created', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'With Inventory',
        'price_amount' => 2500,
    ]);

    expect($product->variants->first()->inventoryItem)->not->toBeNull();
});

it('allows null SKUs', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'No SKU Product',
        'price_amount' => 2500,
    ]);

    $v2 = ProductVariant::factory()->for($product)->create(['sku' => null]);

    expect($product->variants()->count())->toBe(2);
});
