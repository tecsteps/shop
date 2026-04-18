<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Services\ProductService;

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
    $this->service = app(ProductService::class);
});

it('lists products for the current store', function (): void {
    Product::factory()->count(5)->create(['store_id' => $this->store->id]);

    expect(Product::query()->count())->toBe(5);
});

it('creates a product with a default variant', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'New Tee',
        'price_amount' => 2500,
    ]);

    expect($product->handle)->toBe('new-tee');
    expect($product->variants)->toHaveCount(1);
    expect($product->variants->first()->is_default)->toBeTrue();
    expect($product->variants->first()->inventoryItem)->not->toBeNull();
});

it('generates a unique handle from the title', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Summer T-Shirt',
    ]);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function (): void {
    $first = $this->service->create($this->store, ['title' => 'T-Shirt']);
    $second = $this->service->create($this->store, ['title' => 'T-Shirt']);

    expect($first->handle)->toBe('t-shirt');
    expect($second->handle)->toBe('t-shirt-1');
});

it('updates a product', function (): void {
    $product = $this->service->create($this->store, ['title' => 'Before']);

    $this->service->update($product, [
        'title' => 'After',
        'description_html' => '<p>Updated</p>',
    ]);

    $updated = $product->fresh();
    expect($updated->title)->toBe('After');
    expect($updated->description_html)->toBe('<p>Updated</p>');
});

it('transitions product from draft to active', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Priced Product',
        'price_amount' => 1500,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    $fresh = $product->fresh();
    expect($fresh->status)->toBe(ProductStatus::Active);
    expect($fresh->published_at)->not->toBeNull();
});

it('rejects draft to active without a priced variant', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Unpriced Product',
        'price_amount' => 0,
    ]);

    expect(fn () => $this->service->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);

    expect($product->fresh()->status)->toBe(ProductStatus::Draft);
});

it('transitions product from active to archived', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'To Archive',
        'price_amount' => 1000,
    ]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $this->service->transitionStatus($product, ProductStatus::Archived);

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

it('prevents active to draft when order lines exist', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Ordered',
        'price_amount' => 1000,
    ]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $order = \App\Models\Order::factory()->create(['store_id' => $this->store->id]);
    \App\Models\OrderLine::factory()->create([
        'order_id' => $order->id,
        'variant_id' => $product->variants()->first()->id,
    ]);

    expect(fn () => $this->service->transitionStatus($product, ProductStatus::Draft))
        ->toThrow(InvalidProductTransitionException::class);
});

it('hard deletes a draft product with no order references', function (): void {
    $product = $this->service->create($this->store, ['title' => 'Deletable']);
    $id = $product->id;

    $this->service->delete($product);

    expect(Product::query()->find($id))->toBeNull();
});

it('prevents deletion of an active product', function (): void {
    $product = $this->service->create($this->store, [
        'title' => 'Active',
        'price_amount' => 1000,
    ]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    expect(fn () => $this->service->delete($product))
        ->toThrow(InvalidProductTransitionException::class);
});

it('filters products by status', function (): void {
    Product::factory()->count(3)->active()->create(['store_id' => $this->store->id]);
    Product::factory()->count(2)->create(['store_id' => $this->store->id, 'status' => ProductStatus::Draft]);
    Product::factory()->archived()->create(['store_id' => $this->store->id]);

    expect(Product::query()->where('status', ProductStatus::Active)->count())->toBe(3);
    expect(Product::query()->where('status', ProductStatus::Draft)->count())->toBe(2);
});
