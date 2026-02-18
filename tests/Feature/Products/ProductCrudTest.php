<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->productService = new ProductService(new HandleGenerator);
});

it('lists products for the current store', function () {
    Product::factory()->count(5)->create(['store_id' => $this->ctx['store']->id]);

    expect(Product::count())->toBe(5);
});

it('creates a product with a default variant', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Test Product',
        'description_html' => '<p>A test product</p>',
        'price_amount' => 2500,
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->title)->toBe('Test Product')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue();
});

it('generates a unique handle from the title', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Summer T-Shirt',
        'price_amount' => 1000,
    ]);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function () {
    $this->productService->create($this->ctx['store'], [
        'title' => 'T-Shirt',
        'price_amount' => 1000,
    ]);

    $product2 = $this->productService->create($this->ctx['store'], [
        'title' => 'T-Shirt',
        'price_amount' => 1000,
    ]);

    expect($product2->handle)->toBe('t-shirt-1');
});

it('updates a product', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Original Title',
        'price_amount' => 1000,
    ]);

    $updated = $this->productService->update($product, [
        'title' => 'Updated Title',
        'description_html' => '<p>Updated description</p>',
    ]);

    expect($updated->title)->toBe('Updated Title')
        ->and($updated->description_html)->toBe('<p>Updated description</p>')
        ->and($updated->handle)->toBe('updated-title');
});

it('transitions product from draft to active', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Draft Product',
        'price_amount' => 2500,
    ]);

    $this->productService->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active)
        ->and($product->fresh()->published_at)->not->toBeNull();
});

it('transitions product from active to archived', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Active Product',
        'price_amount' => 2500,
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $this->productService->transitionStatus($product->fresh(), ProductStatus::Archived);

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

it('rejects invalid status transition', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Draft Product',
        'price_amount' => 1000,
    ]);

    $this->productService->transitionStatus($product, ProductStatus::Archived);
})->throws(\InvalidArgumentException::class);

it('hard deletes a draft product with no order references', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Draft Product',
        'price_amount' => 1000,
    ]);

    $this->productService->delete($product);

    expect(Product::withoutGlobalScopes()->find($product->id))->toBeNull();
});

it('prevents deletion of non-draft products', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $this->productService->delete($product);
})->throws(\LogicException::class);

it('filters products by status', function () {
    Product::factory()->count(3)->active()->create(['store_id' => $this->ctx['store']->id]);
    Product::factory()->count(2)->create(['store_id' => $this->ctx['store']->id, 'status' => ProductStatus::Draft]);
    Product::factory()->count(1)->archived()->create(['store_id' => $this->ctx['store']->id]);

    expect(Product::where('status', ProductStatus::Active)->count())->toBe(3)
        ->and(Product::where('status', ProductStatus::Draft)->count())->toBe(2)
        ->and(Product::where('status', ProductStatus::Archived)->count())->toBe(1);
});

it('searches products by title', function () {
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Organic Cotton Hoodie',
    ]);
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Silk Scarf',
    ]);

    $results = Product::where('title', 'like', '%cotton%')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Organic Cotton Hoodie');
});
