<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->productService = app(ProductService::class);
});

it('creates a product with a default variant', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Test Product',
        'description_html' => '<p>A test product</p>',
        'price_amount' => 2500,
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue();
});

it('generates a unique handle from the title', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Summer T-Shirt',
        'price_amount' => 2500,
    ]);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function () {
    $this->productService->create($this->ctx['store'], ['title' => 'T-Shirt', 'price_amount' => 2500]);
    $p2 = $this->productService->create($this->ctx['store'], ['title' => 'T-Shirt', 'price_amount' => 2500]);

    expect($p2->handle)->toBe('t-shirt-1');
});

it('updates a product', function () {
    $product = $this->productService->create($this->ctx['store'], ['title' => 'Original', 'price_amount' => 2500]);
    $updated = $this->productService->update($product, ['title' => 'Updated Title']);

    expect($updated->title)->toBe('Updated Title');
});

it('transitions product from draft to active', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Activatable',
        'price_amount' => 2500,
    ]);

    $this->productService->transitionStatus($product, ProductStatus::Active);
    $product->refresh();

    expect($product->status)->toBe(ProductStatus::Active);
});

it('rejects draft to active without a priced variant', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'No Price',
        'price_amount' => 0,
    ]);

    $this->productService->transitionStatus($product, ProductStatus::Active);
})->throws(\InvalidArgumentException::class);

it('transitions product from active to archived', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Active Product',
        'price_amount' => 2500,
    ]);
    $this->productService->transitionStatus($product, ProductStatus::Active);

    $this->productService->transitionStatus($product->refresh(), ProductStatus::Archived);
    $product->refresh();

    expect($product->status)->toBe(ProductStatus::Archived);
});

it('hard deletes a draft product with no order references', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Deletable',
        'price_amount' => 2500,
    ]);

    $this->productService->delete($product);
    expect(Product::withoutGlobalScopes()->find($product->id))->toBeNull();
});

it('prevents deletion of product with order references', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Has Orders',
        'price_amount' => 2500,
    ]);

    \App\Models\OrderLine::factory()->create(['product_id' => $product->id]);

    $this->productService->delete($product);
})->throws(\InvalidArgumentException::class);
