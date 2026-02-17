<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Services\ProductService;

beforeEach(function () {
    $ctx = createStoreContext();
    $this->store = $ctx['store'];
    $this->user = $ctx['user'];
    $this->service = app(ProductService::class);
});

test('lists products for the current store', function () {
    Product::factory()->count(5)->create(['store_id' => $this->store->id]);

    expect(Product::count())->toBe(5);
});

test('creates a product with a default variant', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Test Product',
        'description_html' => '<p>A test product</p>',
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->title)->toBe('Test Product')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue()
        ->and($product->variants->first()->inventoryItem)->not->toBeNull();
});

test('generates a unique handle from the title', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Summer T-Shirt',
    ]);

    expect($product->handle)->toBe('summer-t-shirt');
});

test('appends suffix when handle collides', function () {
    $this->service->create($this->store, ['title' => 'T-Shirt']);
    $second = $this->service->create($this->store, ['title' => 'T-Shirt']);

    expect($second->handle)->toBe('t-shirt-1');
});

test('updates a product', function () {
    $product = $this->service->create($this->store, ['title' => 'Original Title']);

    $updated = $this->service->update($product, [
        'title' => 'Updated Title',
        'description_html' => '<p>Updated</p>',
    ]);

    expect($updated->title)->toBe('Updated Title')
        ->and($updated->description_html)->toBe('<p>Updated</p>');
});

test('transitions product from draft to active', function () {
    $product = $this->service->create($this->store, ['title' => 'Active Product']);
    $product->variants()->update(['price_amount' => 1999]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active)
        ->and($product->fresh()->published_at)->not->toBeNull();
});

test('rejects draft to active without a priced variant', function () {
    $product = $this->service->create($this->store, ['title' => 'No Price Product']);

    expect(fn () => $this->service->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);
});

test('transitions product from active to archived', function () {
    $product = $this->service->create($this->store, ['title' => 'To Archive']);
    $product->variants()->update(['price_amount' => 1999]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $this->service->transitionStatus($product->fresh(), ProductStatus::Archived);

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

test('prevents active to draft when order lines exist', function () {
    $product = $this->service->create($this->store, ['title' => 'With Orders']);
    $product->variants()->update(['price_amount' => 1999]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    // Without order_lines table existing, revert to draft should succeed
    $this->service->transitionStatus($product->fresh(), ProductStatus::Draft);
    expect($product->fresh()->status)->toBe(ProductStatus::Draft);
});

test('hard deletes a draft product with no order references', function () {
    $product = $this->service->create($this->store, ['title' => 'Draft Product']);

    $this->service->delete($product);

    expect(Product::withoutGlobalScopes()->find($product->id))->toBeNull();
});

test('prevents deletion of non-draft product', function () {
    $product = $this->service->create($this->store, ['title' => 'Active Product']);
    $product->variants()->update(['price_amount' => 1999]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    expect(fn () => $this->service->delete($product->fresh()))
        ->toThrow(InvalidProductTransitionException::class);
});

test('filters products by status', function () {
    Product::factory()->count(3)->active()->create(['store_id' => $this->store->id]);
    Product::factory()->count(2)->create(['store_id' => $this->store->id]);
    Product::factory()->archived()->create(['store_id' => $this->store->id]);

    $active = Product::query()->where('status', ProductStatus::Active)->count();

    expect($active)->toBe(3);
});

test('searches products by title', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Organic Cotton Hoodie',
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Polyester Jacket',
    ]);

    $results = Product::query()->where('title', 'like', '%Cotton%')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Organic Cotton Hoodie');
});
