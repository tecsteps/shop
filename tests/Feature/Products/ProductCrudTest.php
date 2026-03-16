<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Services\ProductService;

it('lists products for the current store', function () {
    $context = createStoreContext();

    Product::factory()->count(5)->create(['store_id' => $context['store']->id]);

    expect(Product::count())->toBe(5);
});

it('creates a product with a default variant', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], [
        'title' => 'Test Product',
        'description_html' => '<p>A test product</p>',
        'price_amount' => 2500,
    ]);

    expect($product)->not->toBeNull();
    expect($product->title)->toBe('Test Product');
    expect($product->status)->toBe(ProductStatus::Draft);
    expect($product->variants)->toHaveCount(1);

    $defaultVariant = $product->variants->first();
    expect($defaultVariant->is_default)->toBeTrue();
    expect($defaultVariant->inventoryItem)->not->toBeNull();
});

it('generates a unique handle from the title', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], [
        'title' => 'Summer T-Shirt',
    ]);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product1 = $service->create($context['store'], ['title' => 'T-Shirt']);
    $product2 = $service->create($context['store'], ['title' => 'T-Shirt']);

    expect($product1->handle)->toBe('t-shirt');
    expect($product2->handle)->toBe('t-shirt-1');
});

it('updates a product', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], ['title' => 'Original Title']);

    $updated = $service->update($product, [
        'title' => 'Updated Title',
        'description_html' => '<p>Updated description</p>',
    ]);

    expect($updated->title)->toBe('Updated Title');
    expect($updated->description_html)->toBe('<p>Updated description</p>');
});

it('transitions product from draft to active', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], [
        'title' => 'Activatable Product',
        'price_amount' => 2500,
    ]);

    $service->transitionStatus($product, ProductStatus::Active);

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Active);
    expect($product->published_at)->not->toBeNull();
});

it('rejects draft to active without a priced variant', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], [
        'title' => 'No Price Product',
        'price_amount' => 0,
    ]);

    expect(fn () => $service->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);
});

it('transitions product from active to archived', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], [
        'title' => 'Active Product',
        'price_amount' => 2500,
    ]);

    $service->transitionStatus($product, ProductStatus::Active);
    $service->transitionStatus($product, ProductStatus::Archived);

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Archived);
});

it('hard deletes a draft product', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], ['title' => 'Draft Product']);

    $productId = $product->id;
    $service->delete($product);

    expect(Product::withoutGlobalScopes()->find($productId))->toBeNull();
});

it('prevents deletion of non-draft product', function () {
    $context = createStoreContext();

    $service = app(ProductService::class);
    $product = $service->create($context['store'], [
        'title' => 'Active Product',
        'price_amount' => 2500,
    ]);
    $service->transitionStatus($product, ProductStatus::Active);

    expect(fn () => $service->delete($product))
        ->toThrow(InvalidProductTransitionException::class);
});

it('filters products by status', function () {
    $context = createStoreContext();

    Product::factory()->count(3)->active()->create(['store_id' => $context['store']->id]);
    Product::factory()->count(2)->create(['store_id' => $context['store']->id]); // draft
    Product::factory()->archived()->create(['store_id' => $context['store']->id]);

    expect(Product::where('status', ProductStatus::Active)->count())->toBe(3);
    expect(Product::where('status', ProductStatus::Draft)->count())->toBe(2);
    expect(Product::where('status', ProductStatus::Archived)->count())->toBe(1);
});

it('searches products by title', function () {
    $context = createStoreContext();

    Product::factory()->create([
        'store_id' => $context['store']->id,
        'title' => 'Organic Cotton Hoodie',
    ]);
    Product::factory()->create([
        'store_id' => $context['store']->id,
        'title' => 'Silk Blouse',
    ]);

    $results = Product::where('title', 'like', '%cotton%')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Organic Cotton Hoodie');
});
