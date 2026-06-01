<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Services\ProductService;

beforeEach(function () {
    $context = createStoreContext();
    $this->store = $context['store'];
    $this->service = app(ProductService::class);
});

it('lists products for the current store', function () {
    Product::factory()->count(5)->create(['store_id' => $this->store->id]);

    expect(Product::count())->toBe(5);
});

it('creates a product with a default variant', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Basic Tee',
        'description_html' => '<p>A tee.</p>',
        'status' => ProductStatus::Draft,
    ]);

    expect($product->status)->toBe(ProductStatus::Draft)
        ->and($product->variants()->count())->toBe(1)
        ->and($product->variants()->first()->is_default)->toBeTrue()
        ->and($product->variants()->first()->inventoryItem)->not->toBeNull();
});

it('generates a unique handle from the title', function () {
    $product = $this->service->create($this->store, ['title' => 'Summer T-Shirt']);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function () {
    $first = $this->service->create($this->store, ['title' => 'T-Shirt']);
    $second = $this->service->create($this->store, ['title' => 'T-Shirt']);

    expect($first->handle)->toBe('t-shirt')
        ->and($second->handle)->toBe('t-shirt-1');
});

it('updates a product', function () {
    $product = $this->service->create($this->store, ['title' => 'Old Title']);

    $this->service->update($product, [
        'title' => 'New Title',
        'description_html' => '<p>Updated.</p>',
    ]);

    expect($product->fresh()->title)->toBe('New Title')
        ->and($product->fresh()->description_html)->toBe('<p>Updated.</p>');
});

it('transitions product from draft to active', function () {
    $product = $this->service->create($this->store, ['title' => 'Publishable']);
    $product->variants()->first()->update(['price_amount' => 2500]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active)
        ->and($product->fresh()->published_at)->not->toBeNull();
});

it('rejects draft to active without a priced variant', function () {
    $product = $this->service->create($this->store, ['title' => 'Unpriced']);
    $product->variants()->first()->update(['price_amount' => 0]);

    expect(fn () => $this->service->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);

    expect($product->fresh()->status)->toBe(ProductStatus::Draft);
});

it('transitions product from active to archived', function () {
    $product = $this->service->create($this->store, ['title' => 'Active One']);
    $product->variants()->first()->update(['price_amount' => 2500]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $this->service->transitionStatus($product->fresh(), ProductStatus::Archived);

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

it('prevents active to draft when order lines exist', function () {
    $product = $this->service->create($this->store, ['title' => 'Ordered Product']);
    $variant = $product->variants()->first();
    $variant->update(['price_amount' => 2500]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    fakeOrderLineFor($variant->id);

    expect(fn () => $this->service->transitionStatus($product->fresh(), ProductStatus::Draft))
        ->toThrow(InvalidProductTransitionException::class);
});

it('hard deletes a draft product with no order references', function () {
    $product = $this->service->create($this->store, ['title' => 'Disposable']);

    $this->service->delete($product);

    expect(Product::find($product->id))->toBeNull();
});

it('prevents deletion of product with order references', function () {
    $product = $this->service->create($this->store, ['title' => 'Referenced']);
    $variant = $product->variants()->first();

    fakeOrderLineFor($variant->id);

    expect(fn () => $this->service->delete($product))
        ->toThrow(InvalidProductTransitionException::class);
});

it('filters products by status', function () {
    Product::factory()->count(3)->active()->create(['store_id' => $this->store->id]);
    Product::factory()->count(2)->create(['store_id' => $this->store->id]);
    Product::factory()->count(1)->archived()->create(['store_id' => $this->store->id]);

    expect(Product::where('status', ProductStatus::Active->value)->count())->toBe(3);
});

it('searches products by title', function () {
    Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Organic Cotton Hoodie']);
    Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Wool Beanie']);

    $results = Product::where('title', 'like', '%cotton%')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Organic Cotton Hoodie');
});
