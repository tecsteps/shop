<?php

use App\Exceptions\DuplicateSkuException;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\VariantMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $this->store);
    $this->product = Product::factory()->for($this->store)->create();
    $this->matrix = app(VariantMatrixService::class);
});

it('creates the cartesian product of option values', function () {
    $size = ProductOption::factory()->for($this->product)->create(['name' => 'Size', 'position' => 0]);
    ProductOptionValue::factory()->for($size, 'option')->createMany([
        ['value' => 'S', 'position' => 0],
        ['value' => 'M', 'position' => 1],
        ['value' => 'L', 'position' => 2],
    ]);
    $color = ProductOption::factory()->for($this->product)->create(['name' => 'Color', 'position' => 1]);
    ProductOptionValue::factory()->for($color, 'option')->createMany([
        ['value' => 'Red', 'position' => 0],
        ['value' => 'Blue', 'position' => 1],
    ]);

    $this->matrix->rebuildMatrix($this->product);

    expect($this->product->variants()->count())->toBe(6)
        ->and($this->product->variants()->where('is_default', true)->count())->toBe(1)
        ->and($this->product->variants()->withCount('optionValues')->get()->pluck('option_values_count')->unique()->all())
        ->toBe([2]);
});

it('preserves matching variants and removes unreferenced orphaned variants', function () {
    $size = ProductOption::factory()->for($this->product)->create(['name' => 'Size', 'position' => 0]);
    $small = ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'S', 'position' => 0]);
    $medium = ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'M', 'position' => 1]);
    $this->matrix->rebuildMatrix($this->product);
    $smallVariant = ProductVariant::whereHas('optionValues', fn ($query) => $query->whereKey($small))->firstOrFail();
    $smallVariant->update(['price_amount' => 4321]);
    $mediumVariantId = ProductVariant::whereHas('optionValues', fn ($query) => $query->whereKey($medium))->valueOrFail('id');

    $medium->delete();
    ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'L', 'position' => 1]);
    $this->matrix->rebuildMatrix($this->product);

    expect($smallVariant->refresh()->price_amount)->toBe(4321)
        ->and(ProductVariant::find($mediumVariantId))->toBeNull()
        ->and($this->product->variants()->count())->toBe(2);
});

it('auto creates one default variant for products without options', function () {
    $this->matrix->rebuildMatrix($this->product);

    expect($this->product->variants()->count())->toBe(1)
        ->and($this->product->variants()->first()->is_default)->toBeTrue();
});

it('enforces sku uniqueness within a store but allows null and cross-store skus', function () {
    ProductVariant::factory()->for($this->product)->create(['sku' => 'TSH-001']);

    expect(fn () => ProductVariant::factory()->for($this->product)->create(['sku' => 'TSH-001']))
        ->toThrow(DuplicateSkuException::class);

    ProductVariant::factory()->for($this->product)->count(2)->sequence(['sku' => null], ['sku' => null])->create();
    $otherStore = Store::factory()->create();
    $otherProduct = Product::factory()->for($otherStore)->create();
    $crossStore = ProductVariant::factory()->for($otherProduct)->create(['sku' => 'TSH-001']);

    expect($crossStore->exists)->toBeTrue();
});
