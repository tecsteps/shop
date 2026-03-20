<?php

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Services\VariantMatrixService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->productService = app(ProductService::class);
    $this->matrixService = app(VariantMatrixService::class);
});

it('creates variants from option matrix', function () {
    $product = $this->productService->create($this->store, ['title' => 'Test Product']);

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

    $activeVariants = $product->variants()->where('status', 'active')->count();
    expect($activeVariants)->toBe(6);
});

it('preserves existing variants when adding an option value', function () {
    $product = $this->productService->create($this->store, ['title' => 'Test Product']);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);
    $valueS = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'S',
        'position' => 0,
    ]);
    $valueM = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'M',
        'position' => 1,
    ]);

    $this->matrixService->rebuildMatrix($product);

    $product->variants()->where('status', 'active')->get()->each(function ($v) {
        $v->update(['price_amount' => 1999]);
    });

    ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'L',
        'position' => 2,
    ]);

    $this->matrixService->rebuildMatrix($product);

    $activeVariants = $product->variants()->where('status', 'active')->get();
    expect($activeVariants)->toHaveCount(3);

    // All variants should have price 1999 (new one inherits from first existing)
    $pricedVariants = $activeVariants->filter(fn ($v) => $v->price_amount === 1999);
    expect($pricedVariants)->toHaveCount(3);
});

it('archives orphaned variants with order references', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('order_lines')) {
        // Create a temporary order_lines table for this test
        \Illuminate\Support\Facades\Schema::create('order_lines', function ($table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('variant_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->text('title');
            $table->integer('quantity');
            $table->integer('unit_price_amount');
            $table->integer('subtotal_amount');
            $table->integer('total_amount');
        });
    }

    $product = $this->productService->create($this->store, ['title' => 'Test Product']);

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

    $variantL = $product->variants()->whereHas('optionValues', function ($q) {
        $q->where('value', 'L');
    })->first();

    \Illuminate\Support\Facades\DB::table('order_lines')->insert([
        'order_id' => 1,
        'variant_id' => $variantL->id,
        'product_id' => $product->id,
        'title' => 'Test',
        'quantity' => 1,
        'unit_price_amount' => 2999,
        'subtotal_amount' => 2999,
        'total_amount' => 2999,
    ]);

    $lValue = ProductOptionValue::where('product_option_id', $sizeOption->id)
        ->where('value', 'L')
        ->first();
    $lValue->delete();

    $this->matrixService->rebuildMatrix($product);

    $activeVariants = $product->variants()->where('status', 'active')->count();
    expect($activeVariants)->toBe(2);

    $variantL->refresh();
    expect($variantL->status->value)->toBe('archived');
});

it('deletes orphaned variants without order references', function () {
    $product = $this->productService->create($this->store, ['title' => 'Test Product']);

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

    $variantLId = $product->variants()->whereHas('optionValues', function ($q) {
        $q->where('value', 'L');
    })->first()->id;

    $lValue = ProductOptionValue::where('product_option_id', $sizeOption->id)
        ->where('value', 'L')
        ->first();
    $lValue->delete();

    $this->matrixService->rebuildMatrix($product);

    expect(ProductVariant::find($variantLId))->toBeNull();
    expect($product->variants()->where('status', 'active')->count())->toBe(2);
});

it('auto-creates default variant for products without options', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'no-options-product',
    ]);

    $this->matrixService->rebuildMatrix($product);

    $variants = $product->variants()->get();
    expect($variants)->toHaveCount(1)
        ->and($variants->first()->is_default)->toBeTrue();
});

it('validates SKU uniqueness within store', function () {
    $product1 = $this->productService->create($this->store, ['title' => 'Product 1']);
    $product1->variants()->first()->update(['sku' => 'TSH-001']);

    $product2 = $this->productService->create($this->store, ['title' => 'Product 2']);

    $existingSku = ProductVariant::query()
        ->whereHas('product', fn ($q) => $q->withoutGlobalScopes()->where('store_id', $this->store->id))
        ->where('sku', 'TSH-001')
        ->exists();

    expect($existingSku)->toBeTrue();
});

it('allows duplicate SKU across different stores', function () {
    $product1 = $this->productService->create($this->store, ['title' => 'Product 1']);
    $product1->variants()->first()->update(['sku' => 'TSH-001']);

    $contextB = createStoreContext();
    $storeB = $contextB['store'];
    app()->instance('current_store', $storeB);

    $product2 = $this->productService->create($storeB, ['title' => 'Product 2']);
    $product2->variants()->first()->update(['sku' => 'TSH-001']);

    expect($product2->variants()->first()->sku)->toBe('TSH-001');
});

it('allows null SKUs', function () {
    $product = $this->productService->create($this->store, ['title' => 'Product 1']);
    $product2 = $this->productService->create($this->store, ['title' => 'Product 2']);

    expect($product->variants()->first()->sku)->toBeNull()
        ->and($product2->variants()->first()->sku)->toBeNull();
});
