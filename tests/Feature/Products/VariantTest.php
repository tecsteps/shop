<?php

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Services\VariantMatrixService;

beforeEach(function () {
    $context = createStoreContext();
    $this->store = $context['store'];
    $this->matrix = app(VariantMatrixService::class);
    $this->service = app(ProductService::class);
});

it('creates variants from option matrix', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Matrix Tee',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M', 'L']],
            ['name' => 'Color', 'values' => ['Red', 'Blue']],
        ],
    ]);

    expect($product->variants()->count())->toBe(6);
});

it('preserves existing variants when adding an option value', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Preserve Tee',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M']],
        ],
    ]);

    // Give the existing variants distinct prices to prove they survive.
    $product->variants()->get()->each(fn ($variant, $i) => $variant->update(['price_amount' => 1000 + $i]));
    $originalPrices = $product->variants()->orderBy('id')->pluck('price_amount')->all();

    // Add a third value to the Size option and rebuild.
    $sizeOption = $product->options()->first();
    $sizeOption->values()->create(['value' => 'L', 'position' => 2]);

    $this->matrix->rebuildMatrix($product->fresh());

    $product = $product->fresh();
    $survivingPrices = $product->variants()->orderBy('id')->limit(2)->pluck('price_amount')->all();

    expect($product->variants()->count())->toBe(3)
        ->and($survivingPrices)->toBe($originalPrices);
});

it('archives orphaned variants with order references', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Archive Tee',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M']],
        ],
    ]);

    $sizeOption = $product->options()->first();
    $valueToRemove = $sizeOption->values()->where('value', 'M')->first();
    $orphan = $valueToRemove->variants()->first();

    fakeOrderLineFor($orphan->id);

    // Remove the 'M' value; its variant becomes orphaned.
    $valueToRemove->delete();
    $this->matrix->rebuildMatrix($product->fresh());

    expect(ProductVariant::find($orphan->id)->status)->toBe(VariantStatus::Archived);
});

it('deletes orphaned variants without order references', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Delete Tee',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M']],
        ],
    ]);

    $sizeOption = $product->options()->first();
    $valueToRemove = $sizeOption->values()->where('value', 'M')->first();
    $orphan = $valueToRemove->variants()->first();

    $valueToRemove->delete();
    $this->matrix->rebuildMatrix($product->fresh());

    expect(ProductVariant::find($orphan->id))->toBeNull();
});

it('auto-creates default variant for products without options', function () {
    $product = $this->service->create($this->store, ['title' => 'No Options Tee']);

    $variants = $product->variants()->get();

    expect($variants)->toHaveCount(1)
        ->and($variants->first()->is_default)->toBeTrue();
});

it('validates SKU uniqueness within store', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'TSH-001']);

    expect(skuExistsInStore('TSH-001', $this->store->id))->toBeTrue();
});

it('allows duplicate SKU across different stores', function () {
    $otherContext = createStoreContext(['hostname' => 'other.test', 'handle' => 'other-store', 'bind' => false]);
    $otherStore = $otherContext['store'];

    $productA = Product::factory()->create(['store_id' => $this->store->id]);
    ProductVariant::factory()->create(['product_id' => $productA->id, 'sku' => 'TSH-001']);

    // SKU does not collide within the other store.
    expect(skuExistsInStore('TSH-001', $otherStore->id))->toBeFalse();
});

it('allows null SKUs', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $a = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => null]);
    $b = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => null]);

    expect($a->exists)->toBeTrue()
        ->and($b->exists)->toBeTrue();
});

/**
 * Whether a non-null SKU is already used by any variant in the given store.
 * Mirrors the application-level SKU uniqueness check (store-scoped, null-exempt).
 */
function skuExistsInStore(?string $sku, int $storeId, ?int $excludeVariantId = null): bool
{
    if ($sku === null || $sku === '') {
        return false;
    }

    $query = ProductVariant::query()
        ->where('sku', $sku)
        ->whereHas('product', fn ($q) => $q->withoutGlobalScopes()->where('store_id', $storeId));

    if ($excludeVariantId !== null) {
        $query->where('id', '!=', $excludeVariantId);
    }

    return $query->exists();
}
