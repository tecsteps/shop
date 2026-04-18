<?php

use App\Enums\VariantStatus;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Services\VariantMatrixService;

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
    $this->products = app(ProductService::class);
    $this->matrix = app(VariantMatrixService::class);
});

it('creates variants from option matrix', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Shirt']);
    $product->variants()->delete();

    $size = ProductOption::query()->create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $color = ProductOption::query()->create(['product_id' => $product->id, 'name' => 'Color', 'position' => 1]);

    foreach (['S', 'M', 'L'] as $i => $v) {
        ProductOptionValue::query()->create(['product_option_id' => $size->id, 'value' => $v, 'position' => $i]);
    }
    foreach (['Red', 'Blue'] as $i => $v) {
        ProductOptionValue::query()->create(['product_option_id' => $color->id, 'value' => $v, 'position' => $i]);
    }

    $this->matrix->rebuildMatrix($product);

    expect($product->fresh()->variants)->toHaveCount(6);
});

it('preserves existing variants when adding an option value', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Shirt', 'price_amount' => 2000]);
    $product->variants()->delete();

    $size = ProductOption::query()->create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    foreach (['S', 'M'] as $i => $v) {
        ProductOptionValue::query()->create(['product_option_id' => $size->id, 'value' => $v, 'position' => $i]);
    }

    ProductVariant::query()->create([
        'product_id' => $product->id,
        'price_amount' => 1234,
        'currency' => 'USD',
        'position' => 0,
        'status' => VariantStatus::Active,
    ])->optionValues()->sync([$size->values()->where('value', 'S')->first()->id]);

    ProductVariant::query()->create([
        'product_id' => $product->id,
        'price_amount' => 5678,
        'currency' => 'USD',
        'position' => 1,
        'status' => VariantStatus::Active,
    ])->optionValues()->sync([$size->values()->where('value', 'M')->first()->id]);

    ProductOptionValue::query()->create(['product_option_id' => $size->id, 'value' => 'L', 'position' => 2]);

    $this->matrix->rebuildMatrix($product->fresh());

    $fresh = $product->fresh(['variants.optionValues']);
    expect($fresh->variants)->toHaveCount(3);

    $small = $fresh->variants->first(fn ($v) => $v->optionValues->first()?->value === 'S');
    expect($small->price_amount)->toBe(1234);
});

it('archives orphaned variants with order references', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Shirt']);
    $product->variants()->delete();

    $size = ProductOption::query()->create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $sm = ProductOptionValue::query()->create(['product_option_id' => $size->id, 'value' => 'S', 'position' => 0]);
    $md = ProductOptionValue::query()->create(['product_option_id' => $size->id, 'value' => 'M', 'position' => 1]);

    $variantS = ProductVariant::query()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'currency' => 'USD',
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);
    $variantS->optionValues()->sync([$sm->id]);

    $variantM = ProductVariant::query()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'currency' => 'USD',
        'position' => 1,
        'status' => VariantStatus::Active,
    ]);
    $variantM->optionValues()->sync([$md->id]);

    \Schema::create('order_lines', function ($table): void {
        $table->id();
        $table->foreignId('variant_id');
    });
    \DB::table('order_lines')->insert(['variant_id' => $variantS->id]);

    $sm->delete();

    $this->matrix->rebuildMatrix($product->fresh());

    expect(ProductVariant::query()->find($variantS->id)->status)->toBe(VariantStatus::Archived);

    \Schema::drop('order_lines');
});

it('deletes orphaned variants without order references', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Shirt']);
    $product->variants()->delete();

    $size = ProductOption::query()->create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $sm = ProductOptionValue::query()->create(['product_option_id' => $size->id, 'value' => 'S', 'position' => 0]);
    $md = ProductOptionValue::query()->create(['product_option_id' => $size->id, 'value' => 'M', 'position' => 1]);

    $variantS = ProductVariant::query()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'currency' => 'USD',
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);
    $variantS->optionValues()->sync([$sm->id]);

    $variantM = ProductVariant::query()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'currency' => 'USD',
        'position' => 1,
        'status' => VariantStatus::Active,
    ]);
    $variantM->optionValues()->sync([$md->id]);

    $sm->delete();

    $this->matrix->rebuildMatrix($product->fresh());

    expect(ProductVariant::query()->find($variantS->id))->toBeNull();
    expect(ProductVariant::query()->find($variantM->id))->not->toBeNull();
});

it('auto-creates a default variant for products without options', function (): void {
    $product = $this->products->create($this->store, ['title' => 'Simple']);

    expect($product->variants)->toHaveCount(1);
    expect($product->variants->first()->is_default)->toBeTrue();
});

it('validates SKU uniqueness within store', function (): void {
    $product = $this->products->create($this->store, ['title' => 'A']);
    $product->variants->first()->update(['sku' => 'TSH-001']);

    $product2 = $this->products->create($this->store, ['title' => 'B']);
    $variant2 = $product2->variants->first();

    $existing = ProductVariant::query()
        ->where('sku', 'TSH-001')
        ->whereHas('product', fn ($q) => $q->where('store_id', $this->store->id))
        ->where('id', '!=', $variant2->id)
        ->exists();

    expect($existing)->toBeTrue();
});

it('allows duplicate SKU across different stores', function (): void {
    $product = $this->products->create($this->store, ['title' => 'A']);
    $product->variants->first()->update(['sku' => 'TSH-001']);

    $other = $this->createStoreContext(['hostname' => 'other.test']);
    $otherStore = $other['store'];

    $otherProduct = $this->products->create($otherStore, ['title' => 'B']);
    $otherProduct->variants->first()->update(['sku' => 'TSH-001']);

    expect(ProductVariant::query()->where('sku', 'TSH-001')->count())->toBe(2);
});

it('allows null SKUs on multiple variants', function (): void {
    $product = $this->products->create($this->store, ['title' => 'A']);

    $extra = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => null,
        'price_amount' => 1000,
        'currency' => 'USD',
        'position' => 1,
        'status' => VariantStatus::Active,
    ]);

    expect(ProductVariant::query()->whereNull('sku')->where('product_id', $product->id)->count())->toBe(2);
});
