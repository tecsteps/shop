<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\ProductService;

it('creates a product with a default variant', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Summer T-Shirt', 'status' => 'draft']);

    expect($product->variants()->count())->toBe(1);
    expect($product->variants()->first()->is_default)->toBeTrue();
    expect($product->variants()->first()->inventoryItem)->not->toBeNull();
});

it('generates a unique handle from the title', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Summer T-Shirt']);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function () {
    $ctx = createStoreContext();
    app(ProductService::class)->create($ctx['store'], ['title' => 'T-Shirt']);
    $second = app(ProductService::class)->create($ctx['store'], ['title' => 'T-Shirt']);

    expect($second->handle)->toBe('t-shirt-1');
});

it('updates a product', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Old Title']);

    app(ProductService::class)->update($product, ['title' => 'New Title', 'vendor' => 'Acme']);

    $fresh = $product->fresh();
    expect($fresh->title)->toBe('New Title');
    expect($fresh->vendor)->toBe('Acme');
});

it('transitions product from draft to active', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Priced', 'price_amount' => 2500]);

    app(ProductService::class)->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe('active');
    expect($product->fresh()->published_at)->not->toBeNull();
});

it('rejects draft to active without a priced variant', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'No Price', 'price_amount' => 0]);

    expect(fn () => app(ProductService::class)->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);
});

it('transitions product from active to archived', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Priced', 'price_amount' => 2500]);
    app(ProductService::class)->transitionStatus($product, ProductStatus::Active);

    app(ProductService::class)->transitionStatus($product, ProductStatus::Archived);

    expect($product->fresh()->status)->toBe('archived');
});

it('prevents active to draft when order lines exist', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Priced', 'price_amount' => 2500]);
    app(ProductService::class)->transitionStatus($product, ProductStatus::Active);
    OrderLine::factory()->create(['variant_id' => $product->variants()->first()->id]);

    expect(fn () => app(ProductService::class)->transitionStatus($product, ProductStatus::Draft))
        ->toThrow(InvalidProductTransitionException::class);
});

it('hard deletes a draft product with no order references', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Draft']);

    app(ProductService::class)->delete($product);

    expect(Product::withoutGlobalScope(\App\Models\Scopes\StoreScope::class)->find($product->id))->toBeNull();
});

it('prevents deletion of product with order references', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Draft', 'price_amount' => 2500]);
    OrderLine::factory()->create(['variant_id' => $product->variants()->first()->id]);

    expect(fn () => app(ProductService::class)->delete($product))
        ->toThrow(InvalidProductTransitionException::class);
});
