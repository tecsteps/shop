<?php

use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->matrixService = new VariantMatrixService;
    $this->productService = new ProductService(new HandleGenerator);
});

it('creates a default variant when product has no options', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Simple Product',
        'price_amount' => 1500,
    ]);

    $this->matrixService->rebuildMatrix($product);

    $variants = $product->fresh()->variants;
    expect($variants)->toHaveCount(1)
        ->and($variants->first()->is_default)->toBeTrue();
});

it('builds variant matrix from options', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'T-Shirt',
        'price_amount' => 2500,
    ]);

    $sizeOption = ProductOption::create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'L', 'position' => 2]);

    $this->matrixService->rebuildMatrix($product);

    $variants = $product->fresh()->variants()->where('status', VariantStatus::Active)->get();
    expect($variants)->toHaveCount(3);

    foreach ($variants as $variant) {
        expect($variant->inventoryItem)->not->toBeNull();
    }
});

it('builds cartesian product for multiple options', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'T-Shirt',
        'price_amount' => 2500,
    ]);

    $sizeOption = ProductOption::create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    $colorOption = ProductOption::create([
        'product_id' => $product->id,
        'name' => 'Color',
        'position' => 1,
    ]);
    ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Red', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Blue', 'position' => 1]);

    $this->matrixService->rebuildMatrix($product);

    $variants = $product->fresh()->variants()->where('status', VariantStatus::Active)->get();
    expect($variants)->toHaveCount(4);
});

it('preserves existing variants when matrix is rebuilt', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Shoe',
        'price_amount' => 5000,
    ]);

    $sizeOption = ProductOption::create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);
    $small = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    $medium = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    $this->matrixService->rebuildMatrix($product);

    $firstVariant = $product->fresh()->variants()->orderBy('position')->first();
    $firstVariant->update(['sku' => 'SHOE-S', 'price_amount' => 7500]);

    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'L', 'position' => 2]);

    $this->matrixService->rebuildMatrix($product);

    $preserved = ProductVariant::find($firstVariant->id);
    expect($preserved->sku)->toBe('SHOE-S')
        ->and($preserved->price_amount)->toBe(7500);

    $allVariants = $product->fresh()->variants()->where('status', VariantStatus::Active)->get();
    expect($allVariants)->toHaveCount(3);
});

it('removes orphaned variants without order references', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Test',
        'price_amount' => 1000,
    ]);

    $sizeOption = ProductOption::create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);
    $small = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    $medium = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    $this->matrixService->rebuildMatrix($product);

    expect($product->fresh()->variants)->toHaveCount(2);

    $medium->delete();

    $this->matrixService->rebuildMatrix($product);

    $activeVariants = $product->fresh()->variants()->where('status', VariantStatus::Active)->get();
    expect($activeVariants)->toHaveCount(1);
});

it('attaches option values to variants via pivot', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Hat',
        'price_amount' => 1500,
    ]);

    $colorOption = ProductOption::create([
        'product_id' => $product->id,
        'name' => 'Color',
        'position' => 0,
    ]);
    ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Red', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Blue', 'position' => 1]);

    $this->matrixService->rebuildMatrix($product);

    $variants = $product->fresh()->variants()->with('optionValues')->where('status', VariantStatus::Active)->get();

    foreach ($variants as $variant) {
        expect($variant->optionValues)->toHaveCount(1);
    }
});

it('creates inventory items for new variants', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Book',
        'price_amount' => 999,
    ]);

    $sizeOption = ProductOption::create([
        'product_id' => $product->id,
        'name' => 'Format',
        'position' => 0,
    ]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'Paperback', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'Hardcover', 'position' => 1]);

    $this->matrixService->rebuildMatrix($product);

    $inventoryCount = InventoryItem::where('store_id', $this->store->id)->count();
    expect($inventoryCount)->toBeGreaterThanOrEqual(2);
});
