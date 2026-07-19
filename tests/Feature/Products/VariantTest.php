<?php

use App\Enums\VariantStatus;
use App\Services\ProductService;
use App\Services\VariantMatrixService;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->service = app(ProductService::class);
});

test('rebuilding the matrix creates missing combinations', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Matrix',
        'options' => [
            ['name' => 'Size', 'values' => ['Small', 'Medium']],
        ],
        'variant_defaults' => ['price_amount' => 1000],
    ]);

    expect($product->variants)->toHaveCount(2);

    // Add a third value and rebuild.
    $product = $this->service->update($product, [
        'options' => [
            ['name' => 'Size', 'values' => ['Small', 'Medium', 'Large']],
        ],
    ]);

    expect($product->variants)->toHaveCount(3)
        ->and($product->variants->pluck('optionValues')->map(
            fn ($values) => $values->first()->value
        )->sort()->values()->all())->toBe(['Large', 'Medium', 'Small']);
});

test('rebuilding preserves existing variants: price, sku, and inventory stay intact', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Preserve',
        'options' => [
            ['name' => 'Color', 'values' => ['Blue', 'Red']],
        ],
        'variants' => [
            ['option_values' => ['Blue'], 'sku' => 'P-BLU', 'price_amount' => 1500, 'inventory' => ['quantity_on_hand' => 7]],
        ],
    ]);

    $blue = $product->variants->firstWhere('sku', 'P-BLU');

    $product = $this->service->update($product, [
        'options' => [
            ['name' => 'Color', 'values' => ['Blue', 'Red', 'Green']],
        ],
    ]);

    $blueAfter = $product->variants->firstWhere('sku', 'P-BLU');

    expect($blueAfter)->not->toBeNull()
        ->and($blueAfter->id)->toBe($blue->id)
        ->and($blueAfter->price_amount)->toBe(1500)
        ->and($blueAfter->inventoryItem->quantity_on_hand)->toBe(7)
        ->and($product->variants)->toHaveCount(3);
});

test('orphaned variants without order references are deleted', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Shrink',
        'options' => [
            ['name' => 'Color', 'values' => ['Blue', 'Red']],
        ],
    ]);

    expect($product->variants)->toHaveCount(2);

    $product = $this->service->update($product, [
        'options' => [
            ['name' => 'Color', 'values' => ['Blue']],
        ],
    ]);

    expect($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->title())->toBe('Blue');
});

test('orphaned variants with order references are archived, not deleted', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Archive Orphans',
        'options' => [
            ['name' => 'Color', 'values' => ['Blue', 'Red']],
        ],
    ]);

    $red = $product->variants->first(fn ($variant) => $variant->title() === 'Red');

    createOrderLineFor($this->store, $product, $red);

    $product = $this->service->update($product, [
        'options' => [
            ['name' => 'Color', 'values' => ['Blue']],
        ],
    ]);

    $redAfter = $product->variants()->whereKey($red->id)->first();

    expect($redAfter)->not->toBeNull()
        ->and($redAfter->status)->toBe(VariantStatus::Archived)
        ->and($product->variants->where('status', VariantStatus::Active))->toHaveCount(1);
});

test('new variants copy default pricing from the first existing variant', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Defaults',
        'options' => [
            ['name' => 'Size', 'values' => ['Small']],
        ],
        'variants' => [
            ['option_values' => ['Small'], 'price_amount' => 4321, 'currency' => 'EUR'],
        ],
    ]);

    $product = $this->service->update($product, [
        'options' => [
            ['name' => 'Size', 'values' => ['Small', 'Large']],
        ],
    ]);

    $large = $product->variants->first(fn ($variant) => $variant->title() === 'Large');

    expect($large)->not->toBeNull()
        ->and($large->price_amount)->toBe(4321)
        ->and($large->currency)->toBe('EUR')
        ->and($large->inventoryItem)->not->toBeNull();
});

test('a variant with the same SKU in the same store is rejected on update', function () {
    $product = $this->service->create($this->store, [
        'title' => 'SKU Clash',
        'options' => [
            ['name' => 'Size', 'values' => ['Small', 'Large']],
        ],
        'variants' => [
            ['option_values' => ['Small'], 'sku' => 'CLASH-S'],
        ],
    ]);

    $large = $product->variants->first(fn ($variant) => $variant->title() === 'Large');

    $this->service->update($product, [
        'variants' => [
            ['id' => $large->id, 'sku' => 'CLASH-S'],
        ],
    ]);
})->throws(Illuminate\Validation\ValidationException::class);

test('keeping the same SKU on the same variant is allowed', function () {
    $product = $this->service->create($this->store, [
        'title' => 'SKU Keep',
        'variants' => [['sku' => 'KEEP-1', 'price_amount' => 100]],
    ]);

    $variant = $product->variants->first();

    $product = $this->service->update($product, [
        'variants' => [
            ['id' => $variant->id, 'sku' => 'KEEP-1', 'price_amount' => 200],
        ],
    ]);

    expect($product->variants->first()->price_amount)->toBe(200);
});

test('rebuildMatrix is a no-op for products without options', function () {
    $product = $this->service->create($this->store, ['title' => 'No Options']);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    expect($product->refresh()->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue();
});

test('a single default variant is auto-created when no options are given', function () {
    $product = $this->service->create($this->store, ['title' => 'Default Only']);

    expect($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue()
        ->and($product->defaultVariant->id)->toBe($product->variants->first()->id);
});

test('variant titles combine option values in option order', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Titles',
        'options' => [
            ['name' => 'Color', 'values' => ['Blue']],
            ['name' => 'Size', 'values' => ['Medium']],
        ],
    ]);

    expect($product->variants->first()->title())->toBe('Blue / Medium');
});
