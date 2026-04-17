<?php

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\VariantMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(VariantMatrixService::class);
});

test('rebuildMatrix creates default variant when no options exist', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $this->service->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(1);

    $variant = $product->variants()->first();
    expect($variant->is_default)->toBeTrue();
    expect($variant->inventoryItem)->not->toBeNull();
});

test('rebuildMatrix creates variants for single option', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $option = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Small', 'position' => 0]);
    ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Medium', 'position' => 1]);
    ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Large', 'position' => 2]);

    $this->service->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(3);
    expect($product->variants()->get()->every(fn ($v) => $v->inventoryItem !== null))->toBeTrue();
});

test('rebuildMatrix creates variants for multiple options as cartesian product', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    $colorOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Color',
        'position' => 1,
    ]);

    ProductOptionValue::factory()->create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    ProductOptionValue::factory()->create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    ProductOptionValue::factory()->create(['product_option_id' => $colorOption->id, 'value' => 'Red', 'position' => 0]);
    ProductOptionValue::factory()->create(['product_option_id' => $colorOption->id, 'value' => 'Blue', 'position' => 1]);

    $this->service->rebuildMatrix($product);

    // 2 sizes x 2 colors = 4 variants
    expect($product->variants()->count())->toBe(4);
});

test('rebuildMatrix preserves existing variants that match', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $option = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    $small = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Small', 'position' => 0]);
    $medium = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Medium', 'position' => 1]);

    // Create an existing variant for Small
    $existingVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 5000,
        'sku' => 'EXISTING-SKU',
    ]);
    $existingVariant->optionValues()->attach($small->id);
    $existingVariant->inventoryItem()->create([
        'store_id' => $this->store->id,
        'quantity_on_hand' => 25,
    ]);

    $this->service->rebuildMatrix($product);

    // Should have 2 variants (Small preserved, Medium created)
    expect($product->variants()->count())->toBe(2);

    // The existing variant should be preserved with its data
    $preserved = $product->variants()->where('sku', 'EXISTING-SKU')->first();
    expect($preserved)->not->toBeNull();
    expect($preserved->price_amount)->toBe(5000);
    expect($preserved->inventoryItem->quantity_on_hand)->toBe(25);
});

test('rebuildMatrix deletes orphaned variants without order references', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    // Create an orphan variant (no options match it)
    $orphan = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => false,
        'sku' => 'ORPHAN',
    ]);
    $orphan->inventoryItem()->create(['store_id' => $this->store->id]);

    $option = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Small', 'position' => 0]);

    $this->service->rebuildMatrix($product);

    expect(ProductVariant::query()->withoutGlobalScopes()->find($orphan->id))->toBeNull();
});
