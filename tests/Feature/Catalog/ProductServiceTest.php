<?php

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(ProductService::class);
});

test('create product creates product with default variant and inventory item', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Test Product',
        'price_amount' => 1999,
    ]);

    expect($product)
        ->title->toBe('Test Product')
        ->handle->toBe('test-product')
        ->status->toBe(ProductStatus::Draft)
        ->store_id->toBe($this->store->id);

    expect($product->variants)->toHaveCount(1);

    $variant = $product->variants->first();
    expect($variant)
        ->is_default->toBeTrue()
        ->price_amount->toBe(1999)
        ->status->toBe(VariantStatus::Active);

    expect($variant->inventoryItem)->not->toBeNull();
    expect($variant->inventoryItem->quantity_on_hand)->toBe(0);
});

test('create product generates unique handle on collision', function () {
    $this->service->create($this->store, ['title' => 'Test Product', 'price_amount' => 1000]);
    $product2 = $this->service->create($this->store, ['title' => 'Test Product', 'price_amount' => 1000]);

    expect($product2->handle)->toBe('test-product-1');
});

test('update product updates title and handle', function () {
    $product = $this->service->create($this->store, ['title' => 'Old Title', 'price_amount' => 1000]);

    $updated = $this->service->update($product, ['title' => 'New Title']);

    expect($updated->title)->toBe('New Title');
    expect($updated->handle)->toBe('new-title');
});

test('transition draft to active succeeds with priced variant', function () {
    $product = $this->service->create($this->store, ['title' => 'My Product', 'price_amount' => 2000]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active);
    expect($product->fresh()->published_at)->not->toBeNull();
});

test('transition draft to active fails without priced variant', function () {
    $product = $this->service->create($this->store, ['title' => 'My Product', 'price_amount' => 0]);

    expect(fn () => $this->service->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);
});

test('transition draft to archived always succeeds', function () {
    $product = $this->service->create($this->store, ['title' => 'My Product', 'price_amount' => 0]);

    $this->service->transitionStatus($product, ProductStatus::Archived);

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

test('transition active to archived succeeds', function () {
    $product = $this->service->create($this->store, ['title' => 'My Product', 'price_amount' => 2000]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $this->service->transitionStatus($product, ProductStatus::Archived);

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

test('transition archived to active succeeds with priced variant', function () {
    $product = $this->service->create($this->store, ['title' => 'My Product', 'price_amount' => 2000]);
    $this->service->transitionStatus($product, ProductStatus::Active);
    $this->service->transitionStatus($product, ProductStatus::Archived);

    $this->service->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active);
});

test('transition does not overwrite existing published_at', function () {
    $product = $this->service->create($this->store, ['title' => 'My Product', 'price_amount' => 2000]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $originalPublishedAt = $product->fresh()->published_at;

    $this->service->transitionStatus($product, ProductStatus::Archived);
    $this->service->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->published_at->toIso8601String())
        ->toBe($originalPublishedAt->toIso8601String());
});

test('delete succeeds for draft product without order references', function () {
    $product = $this->service->create($this->store, ['title' => 'Deletable Product', 'price_amount' => 1000]);

    $this->service->delete($product);

    expect(Product::query()->withoutGlobalScopes()->find($product->id))->toBeNull();
});

test('delete fails for active product', function () {
    $product = $this->service->create($this->store, ['title' => 'Active Product', 'price_amount' => 2000]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    expect(fn () => $this->service->delete($product))
        ->toThrow(InvalidProductTransitionException::class);
});
