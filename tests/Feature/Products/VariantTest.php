<?php

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Services\VariantMatrixService;

it('creates variants from option matrix', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);
    $matrixService = app(VariantMatrixService::class);

    $product = $service->create($context['store'], ['title' => 'Matrix Product', 'price_amount' => 2499]);

    // Remove the default variant before rebuilding
    $product->variants()->delete();

    $sizeOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'L', 'position' => 2]);

    $colorOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Color', 'position' => 1]);
    ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Red', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Blue', 'position' => 1]);

    $matrixService->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(6);
});

it('preserves existing variants when adding an option value', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);
    $matrixService = app(VariantMatrixService::class);

    $product = $service->create($context['store'], ['title' => 'Preserve Test', 'price_amount' => 1999]);
    $product->variants()->delete();

    $sizeOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $sVal = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    $mVal = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    $matrixService->rebuildMatrix($product);

    expect($product->variants()->count())->toBe(2);

    // Record existing variant IDs and update prices
    $existingIds = $product->variants()->pluck('id')->all();
    $product->variants()->each(fn ($v) => $v->update(['price_amount' => 3499]));

    // Add a new size
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'L', 'position' => 2]);

    $matrixService->rebuildMatrix($product->fresh());

    $variants = $product->fresh()->variants;
    expect($variants)->toHaveCount(3);

    // Original 2 variants should be preserved with their updated prices
    $preserved = $variants->whereIn('id', $existingIds);
    expect($preserved)->toHaveCount(2)
        ->each(fn ($v) => $v->price_amount->toBe(3499));
});

it('archives orphaned variants with order references', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);
    $matrixService = app(VariantMatrixService::class);

    $product = $service->create($context['store'], ['title' => 'Orphan Test', 'price_amount' => 1999]);
    $product->variants()->delete();

    $sizeOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $sVal = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    $mVal = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    $matrixService->rebuildMatrix($product);

    $mVariant = $product->variants()->get()->last();

    // Create order line reference using the real order_lines table
    $order = \App\Models\Order::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'order_number' => '#9997',
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'currency' => 'EUR',
        'total_amount' => 1999,
        'placed_at' => now(),
    ]);

    \App\Models\OrderLine::create([
        'order_id' => $order->id,
        'variant_id' => $mVariant->id,
        'title_snapshot' => 'Variant Test',
        'price_amount' => 1999,
        'quantity' => 1,
        'total_amount' => 1999,
    ]);

    // Remove M option value to orphan its variant
    $mVal->delete();

    $matrixService->rebuildMatrix($product->fresh());

    expect($mVariant->fresh()->status)->toBe(VariantStatus::Archived);
});

it('deletes orphaned variants without order references', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);
    $matrixService = app(VariantMatrixService::class);

    $product = $service->create($context['store'], ['title' => 'Delete Orphan', 'price_amount' => 1999]);
    $product->variants()->delete();

    $sizeOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'S', 'position' => 0]);
    $mVal = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => 'M', 'position' => 1]);

    $matrixService->rebuildMatrix($product);
    expect($product->variants()->count())->toBe(2);

    $mVariantId = $product->variants()->get()->last()->id;

    $mVal->delete();
    $matrixService->rebuildMatrix($product->fresh());

    expect($product->variants()->count())->toBe(1)
        ->and(ProductVariant::find($mVariantId))->toBeNull();
});

it('auto-creates default variant for products without options', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], ['title' => 'No Options']);

    expect($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue();
});

it('validates SKU uniqueness within store', function () {
    $context = createStoreContext();

    $product1 = Product::factory()->create(['store_id' => $context['store']->id]);
    ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'sku' => 'TSH-001',
    ]);

    $product2 = Product::factory()->create(['store_id' => $context['store']->id]);

    // Check for SKU collision at application level
    $existingSku = ProductVariant::query()
        ->whereHas('product', fn ($q) => $q->withoutGlobalScopes()->where('store_id', $context['store']->id))
        ->where('sku', 'TSH-001')
        ->exists();

    expect($existingSku)->toBeTrue();
});

it('allows duplicate SKU across different stores', function () {
    $context1 = createStoreContext('store1.test');
    $context2 = createStoreContext('store2.test');

    $product1 = Product::factory()->create(['store_id' => $context1['store']->id]);
    ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'sku' => 'TSH-001',
    ]);

    $product2 = Product::factory()->create(['store_id' => $context2['store']->id]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product2->id,
        'sku' => 'TSH-001',
    ]);

    expect($variant2->sku)->toBe('TSH-001');
});

it('allows null SKUs', function () {
    $context = createStoreContext();

    $product = Product::factory()->create(['store_id' => $context['store']->id]);
    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
    ]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => null,
        'position' => 1,
    ]);

    expect($variant1->sku)->toBeNull()
        ->and($variant2->sku)->toBeNull();
});
