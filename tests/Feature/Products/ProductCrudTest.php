<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = new ProductService(new HandleGenerator);
});

it('lists products for a store', function () {
    Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    $products = Product::withoutGlobalScopes()->where('store_id', $this->store->id)->get();

    expect($products)->toHaveCount(3);
});

it('creates a product with a default variant', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Test Product',
        'price_amount' => 1999,
    ]);

    expect($product->title)->toBe('Test Product')
        ->and($product->handle)->toBe('test-product')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue()
        ->and($product->variants->first()->price_amount)->toBe(1999);
});

it('generates a unique handle from the title', function () {
    $product = $this->service->create($this->store, [
        'title' => 'My Awesome Product',
    ]);

    expect($product->handle)->toBe('my-awesome-product');
});

it('appends suffix on handle collision', function () {
    $this->service->create($this->store, ['title' => 'Duplicate Name']);
    $product2 = $this->service->create($this->store, ['title' => 'Duplicate Name']);

    expect($product2->handle)->toBe('duplicate-name-1');
});

it('updates a product', function () {
    $product = $this->service->create($this->store, ['title' => 'Original Title']);

    $updated = $this->service->update($product, [
        'title' => 'Updated Title',
        'vendor' => 'New Vendor',
    ]);

    expect($updated->title)->toBe('Updated Title')
        ->and($updated->vendor)->toBe('New Vendor');
});

it('transitions from draft to active', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Draft Product',
        'price_amount' => 1000,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active)
        ->and($product->fresh()->published_at)->not->toBeNull();
});

it('rejects draft to active without priced variant', function () {
    $product = $this->service->create($this->store, [
        'title' => 'No Price Product',
        'price_amount' => 0,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
})->throws(InvalidProductTransitionException::class);

it('transitions from active to archived', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Active Product',
        'price_amount' => 2000,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
    $this->service->transitionStatus($product->fresh(), ProductStatus::Archived);

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

it('prevents active to draft transition', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Active Product',
        'price_amount' => 2000,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
    $this->service->transitionStatus($product->fresh(), ProductStatus::Draft);
})->throws(InvalidProductTransitionException::class);

it('hard deletes a draft product', function () {
    $product = $this->service->create($this->store, ['title' => 'Deletable Product']);

    $this->service->delete($product);

    expect(Product::withoutGlobalScopes()->find($product->id))->toBeNull();
});

it('prevents deletion of non-draft product', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Active Product',
        'price_amount' => 2000,
    ]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $this->service->delete($product->fresh());
})->throws(InvalidProductTransitionException::class);

it('filters products by status', function () {
    Product::factory()->count(2)->create(['store_id' => $this->store->id, 'status' => ProductStatus::Draft]);
    Product::factory()->count(3)->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);

    $drafts = Product::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('status', ProductStatus::Draft)
        ->get();

    $active = Product::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('status', ProductStatus::Active)
        ->get();

    expect($drafts)->toHaveCount(2)
        ->and($active)->toHaveCount(3);
});

it('searches products by title', function () {
    Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Blue Widget']);
    Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Red Widget']);
    Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Green Gadget']);

    $results = Product::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('title', 'like', '%Widget%')
        ->get();

    expect($results)->toHaveCount(2);
});
