<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->service = new ProductService(new HandleGenerator);
});

it('creates a product with a default variant', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Test Product',
        'price_amount' => 2999,
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->title)->toBe('Test Product')
        ->and($product->handle)->toBe('test-product')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->store_id)->toBe($this->store->id)
        ->and($product->variants)->toHaveCount(1);

    $variant = $product->variants->first();
    expect($variant->is_default)->toBeTrue()
        ->and($variant->price_amount)->toBe(2999)
        ->and($variant->inventoryItem)->not->toBeNull();
});

it('creates a product with custom handle', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Test Product',
        'handle' => 'custom-handle',
    ]);

    expect($product->handle)->toBe('custom-handle');
});

it('generates unique handles on collision', function () {
    $this->service->create($this->store, ['title' => 'Widget']);
    $product2 = $this->service->create($this->store, ['title' => 'Widget']);

    expect($product2->handle)->toBe('widget-1');
});

it('updates a product', function () {
    $product = $this->service->create($this->store, ['title' => 'Old Title']);

    $updated = $this->service->update($product, [
        'title' => 'New Title',
        'vendor' => 'Acme Corp',
    ]);

    expect($updated->title)->toBe('New Title')
        ->and($updated->vendor)->toBe('Acme Corp')
        ->and($updated->handle)->toBe('new-title');
});

it('transitions from draft to active when preconditions are met', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Active Product',
        'price_amount' => 1000,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active)
        ->and($product->fresh()->published_at)->not->toBeNull();
});

it('blocks draft to active if no variant has price', function () {
    $product = $this->service->create($this->store, [
        'title' => 'No Price Product',
        'price_amount' => 0,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
})->throws(\App\Exceptions\InvalidProductTransitionException::class);

it('transitions from active to archived', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Test',
        'price_amount' => 1000,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
    $this->service->transitionStatus($product->fresh(), ProductStatus::Archived);

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

it('transitions from archived back to active', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Test',
        'price_amount' => 1000,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
    $this->service->transitionStatus($product->fresh(), ProductStatus::Archived);
    $this->service->transitionStatus($product->fresh(), ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active);
});

it('deletes a draft product with no order references', function () {
    $product = $this->service->create($this->store, ['title' => 'To Delete']);

    $this->service->delete($product);

    expect(Product::find($product->id))->toBeNull();
});

it('blocks deletion of non-draft products', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Test',
        'price_amount' => 1000,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    $this->service->delete($product->fresh());
})->throws(\App\Exceptions\InvalidProductTransitionException::class);

it('stores tags as json array', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Tagged Product',
        'tags' => ['summer', 'sale'],
    ]);

    $fresh = $product->fresh();
    expect($fresh->tags)->toBe(['summer', 'sale']);
});

it('sets published_at only on first activation', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Test',
        'price_amount' => 1000,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
    $firstPublishedAt = $product->fresh()->published_at;

    $this->service->transitionStatus($product->fresh(), ProductStatus::Archived);
    $this->service->transitionStatus($product->fresh(), ProductStatus::Active);

    expect($product->fresh()->published_at->toDateTimeString())
        ->toBe($firstPublishedAt->toDateTimeString());
});
