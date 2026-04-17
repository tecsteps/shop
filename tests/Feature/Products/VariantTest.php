<?php

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->service = new ProductService(new VariantMatrixService);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('builds a variant matrix from product options', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Performance Tee',
        'status' => ProductStatus::Draft->value,
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M', 'L']],
            ['name' => 'Color', 'values' => ['Red', 'Blue']],
        ],
    ]);

    expect($product->variants)->toHaveCount(6);

    foreach ($product->variants as $variant) {
        expect($variant->optionValues)->toHaveCount(2);
    }
});

it('archives orphaned variants when options change', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Merino Cap',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M']],
        ],
    ]);

    expect($product->variants)->toHaveCount(2);

    $this->service->update($product, [
        'options' => [
            ['name' => 'Size', 'values' => ['M', 'L']],
        ],
    ]);

    $product = $product->fresh(['variants']);

    $active = $product->variants->where('status', VariantStatus::Active);
    $archived = $product->variants->where('status', VariantStatus::Archived);

    expect($active)->toHaveCount(2)
        ->and($archived)->toHaveCount(1);
});

it('stores prices as integers in minor units', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Canvas Tote',
    ]);

    $variant = $product->variants->first();
    $variant->price_amount = 1999;
    $variant->compare_at_amount = 2499;
    $variant->save();

    $fresh = ProductVariant::find($variant->id);

    expect($fresh->price_amount)->toBe(1999)
        ->and($fresh->compare_at_amount)->toBe(2499);
});
