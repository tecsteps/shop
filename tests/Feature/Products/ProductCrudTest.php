<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;

it('lists products for the current store', function () {
    $context = createStoreContext();
    $otherStore = Store::factory()->create();

    $products = Product::factory()->count(5)->for($context['store'])->create();
    Product::factory()->count(3)->for($otherStore)->create();

    $listedTitles = Product::query()->pluck('title');

    expect($listedTitles)->toHaveCount(5);

    foreach ($products as $product) {
        expect($listedTitles)->toContain($product->title);
    }
});

it('creates a product with a default variant', function () {
    $context = createStoreContext();

    $product = app(ProductService::class)->create($context['store'], [
        'title' => 'Plain Mug',
        'description_html' => '<p>A plain mug.</p>',
        'status' => ProductStatus::Draft,
    ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'store_id' => $context['store']->getKey(),
        'title' => 'Plain Mug',
        'status' => 'draft',
    ]);

    expect($product->variants)->toHaveCount(1);

    $variant = $product->variants->first();

    expect($variant->is_default)->toBeTrue();
    expect($variant->inventoryItem)->not->toBeNull();
    expect($variant->inventoryItem->quantity_on_hand)->toBe(0);
});

it('generates a unique handle from the title', function () {
    $context = createStoreContext();

    $product = app(ProductService::class)->create($context['store'], [
        'title' => 'Summer T-Shirt',
    ]);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $first = $service->create($context['store'], ['title' => 'T-Shirt']);
    $second = $service->create($context['store'], ['title' => 'T-Shirt']);

    expect($first->handle)->toBe('t-shirt');
    expect($second->handle)->toBe('t-shirt-1');
});

it('updates a product', function () {
    $context = createStoreContext();
    $product = Product::factory()->for($context['store'])->create();

    app(ProductService::class)->update($product, [
        'title' => 'Updated Title',
        'description_html' => '<p>Updated description.</p>',
    ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'title' => 'Updated Title',
        'description_html' => '<p>Updated description.</p>',
    ]);
});

it('transitions product from draft to active', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'Publishable Product',
        'price_amount' => 1999,
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
        'title' => 'Unpriced Product',
    ]);

    expect($product->variants->first()->price_amount)->toBe(0);

    expect(fn () => $service->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);

    expect($product->refresh()->status)->toBe(ProductStatus::Draft);
});

it('transitions product from active to archived', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'Soon Archived',
        'status' => ProductStatus::Active,
        'price_amount' => 1500,
    ]);

    $service->transitionStatus($product, ProductStatus::Archived);

    expect($product->refresh()->status)->toBe(ProductStatus::Archived);
});

it('prevents active to draft when order lines exist')
    ->todo('Phase 5: order_lines table does not exist yet; ProductService already enforces the check once it does');

it('hard deletes a draft product with no order references', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], ['title' => 'Disposable Draft']);
    $variantId = $product->variants->first()->getKey();

    $service->delete($product);

    $this->assertDatabaseMissing('products', ['id' => $product->getKey()]);
    $this->assertDatabaseMissing('product_variants', ['id' => $variantId]);
    $this->assertDatabaseMissing('inventory_items', ['variant_id' => $variantId]);
});

it('prevents deletion of product with order references')
    ->todo('Phase 5: order_lines table does not exist yet; ProductService already enforces the check once it does');

it('filters products by status', function () {
    $context = createStoreContext();

    Product::factory()->count(3)->active()->for($context['store'])->create();
    Product::factory()->count(2)->draft()->for($context['store'])->create();
    Product::factory()->count(1)->archived()->for($context['store'])->create();

    expect(Product::query()->where('status', ProductStatus::Active)->count())->toBe(3);
});

it('searches products by title', function () {
    $context = createStoreContext();

    Product::factory()->for($context['store'])->create(['title' => 'Organic Cotton Hoodie']);
    Product::factory()->for($context['store'])->create(['title' => 'Leather Belt']);

    $results = Product::query()->where('title', 'like', '%cotton%')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Organic Cotton Hoodie');
});
