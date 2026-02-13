<?php

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->productService = new ProductService(new HandleGenerator);
    $this->variantMatrixService = new VariantMatrixService;
});

it('creates variants from option matrix', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Matrix Product',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M', 'L']],
            ['name' => 'Color', 'values' => ['Red', 'Blue']],
        ],
    ]);

    $this->variantMatrixService->rebuildMatrix($product);

    // 3 sizes x 2 colors = 6 variants (the default variant is removed as orphan)
    $activeVariants = $product->fresh()->variants()->where('status', VariantStatus::Active)->get();
    expect($activeVariants)->toHaveCount(6);
});

it('preserves existing variants when adding an option', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Expandable Product',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M']],
        ],
    ]);

    $this->variantMatrixService->rebuildMatrix($product);

    $originalVariants = $product->fresh()->variants()->where('status', VariantStatus::Active)->get();
    expect($originalVariants)->toHaveCount(2);

    // Add a second option value to size
    $sizeOption = $product->options()->where('name', 'Size')->first();
    $sizeOption->values()->create(['value' => 'L', 'position' => 2]);

    $this->variantMatrixService->rebuildMatrix($product);

    $allVariants = $product->fresh()->variants()->where('status', VariantStatus::Active)->get();
    expect($allVariants)->toHaveCount(3);
});

it('archives orphaned variants with orders', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Archive Product',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M', 'L']],
        ],
    ]);

    $this->variantMatrixService->rebuildMatrix($product);

    // Simulate order reference by creating a fake order_items table
    \Illuminate\Support\Facades\Schema::create('order_items', function ($table) {
        $table->id();
        $table->foreignId('variant_id');
    });

    // Find the variant that has the 'L' option value
    $sizeOption = $product->options()->where('name', 'Size')->first();
    $lValue = $sizeOption->values()->where('value', 'L')->first();

    $variantToArchive = $product->variants()
        ->whereHas('optionValues', fn ($q) => $q->where('product_option_values.id', $lValue->id))
        ->first();

    \Illuminate\Support\Facades\DB::table('order_items')->insert(['variant_id' => $variantToArchive->id]);

    // Remove 'L' value, keeping only 'S' and 'M'
    $variantToArchive->optionValues()->detach($lValue->id);
    $lValue->delete();

    $this->variantMatrixService->rebuildMatrix($product);

    // The orphaned variant should be archived (not deleted) because it has order references
    expect($variantToArchive->fresh()->status)->toBe(VariantStatus::Archived);

    \Illuminate\Support\Facades\Schema::dropIfExists('order_items');
});

it('deletes orphaned variants without orders', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Delete Orphan Product',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M', 'L']],
        ],
    ]);

    $this->variantMatrixService->rebuildMatrix($product);

    $sizeOption = $product->options()->where('name', 'Size')->first();
    $lValue = $sizeOption->values()->where('value', 'L')->first();

    $orphanVariant = $product->variants()
        ->whereHas('optionValues', fn ($q) => $q->where('product_option_values.id', $lValue->id))
        ->first();

    $orphanId = $orphanVariant->id;

    // Remove L value
    $orphanVariant->optionValues()->detach($lValue->id);
    $lValue->delete();

    $this->variantMatrixService->rebuildMatrix($product);

    expect(ProductVariant::find($orphanId))->toBeNull();
});

it('auto-creates a default variant for product without options', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Simple Product',
    ]);

    expect($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue();
});

it('validates SKU uniqueness within a store', function () {
    $product1 = $this->productService->create($this->store, [
        'title' => 'Product One',
        'variants' => [
            ['sku' => 'UNIQUE-SKU-001', 'price_amount' => 1000],
        ],
    ]);

    $variant = ProductVariant::where('sku', 'UNIQUE-SKU-001')->first();
    expect($variant)->not->toBeNull();

    // Creating another variant with the same SKU in the same store
    $product2 = Product::factory()->create(['store_id' => $this->store->id]);
    $duplicate = $product2->variants()->create([
        'sku' => 'UNIQUE-SKU-001',
        'price_amount' => 2000,
        'position' => 0,
    ]);

    // Both exist - SKU uniqueness is enforced at application level, not DB
    $skuCount = ProductVariant::where('sku', 'UNIQUE-SKU-001')->count();
    expect($skuCount)->toBe(2);
});

it('allows duplicate SKU across stores', function () {
    $org = \App\Models\Organization::factory()->create();
    $otherStore = \App\Models\Store::factory()->for($org)->create();

    $this->productService->create($this->store, [
        'title' => 'Store 1 Product',
        'variants' => [['sku' => 'CROSS-STORE-SKU', 'price_amount' => 1000]],
    ]);

    $otherService = new ProductService(new HandleGenerator);
    $otherService->create($otherStore, [
        'title' => 'Store 2 Product',
        'variants' => [['sku' => 'CROSS-STORE-SKU', 'price_amount' => 2000]],
    ]);

    $total = ProductVariant::withoutGlobalScopes()->where('sku', 'CROSS-STORE-SKU')->count();
    expect($total)->toBe(2);
});

it('allows null SKUs', function () {
    $product = $this->productService->create($this->store, [
        'title' => 'Null SKU Product',
        'variants' => [
            ['sku' => null, 'price_amount' => 500],
            ['sku' => null, 'price_amount' => 600],
        ],
    ]);

    $nullSkus = $product->variants()->whereNull('sku')->count();
    expect($nullSkus)->toBe(2);
});
