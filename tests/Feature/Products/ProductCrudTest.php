<?php

use App\Enums\ProductStatus;
use App\Models\Product;
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

it('creates a product with variants via ProductService', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Linen Shirt',
        'status' => ProductStatus::Draft->value,
        'options' => [
            [
                'name' => 'Size',
                'values' => ['S', 'M', 'L'],
            ],
        ],
    ]);

    expect($product->variants)->toHaveCount(3)
        ->and($product->handle)->toBe('linen-shirt')
        ->and($product->options)->toHaveCount(1);
});

it('generates unique handles when title collides', function (): void {
    $first = $this->service->create($this->store, ['title' => 'Classic Tee']);
    $second = $this->service->create($this->store, ['title' => 'Classic Tee']);
    $third = $this->service->create($this->store, ['title' => 'Classic Tee']);

    expect($first->handle)->toBe('classic-tee')
        ->and($second->handle)->toBe('classic-tee-2')
        ->and($third->handle)->toBe('classic-tee-3');
});

it('transitions status from draft to active', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Wool Coat',
        'status' => ProductStatus::Draft->value,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active);
});

it('rejects invalid status transitions', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Bomber Jacket',
        'status' => ProductStatus::Active->value,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Draft);
})->throws(\InvalidArgumentException::class);

it('prevents deletion of active products', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Silk Scarf',
        'status' => ProductStatus::Active->value,
    ]);

    $this->service->delete($product);
})->throws(\InvalidArgumentException::class);

it('allows deletion of draft products', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Cotton Socks',
        'status' => ProductStatus::Draft->value,
    ]);

    $this->service->delete($product);

    expect(Product::withoutGlobalScopes()->find($product->id))->toBeNull();
});

it('scopes products to the current store', function (): void {
    $storeA = $this->store;
    $storeB = Store::factory()->create();

    app()->instance('current_store', $storeA);
    $this->service->create($storeA, ['title' => 'Product A']);

    app()->instance('current_store', $storeB);
    $this->service->create($storeB, ['title' => 'Product B']);

    expect(Product::count())->toBe(1)
        ->and(Product::first()->title)->toBe('Product B');

    app()->instance('current_store', $storeA);
    expect(Product::count())->toBe(1)
        ->and(Product::first()->title)->toBe('Product A');
});
