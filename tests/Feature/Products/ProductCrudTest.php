<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductDeletionException;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $this->store);
    $this->products = app(ProductService::class);
});

it('creates a draft product with a default variant and inventory', function () {
    $product = $this->products->create($this->store, [
        'title' => 'Summer T-Shirt',
        'status' => ProductStatus::Draft,
        'tags' => ['summer'],
    ]);

    expect($product->handle)->toBe('summer-t-shirt')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue()
        ->and($product->variants->first()->inventoryItem)->not->toBeNull()
        ->and($product->variants->first()->inventoryItem->quantity_on_hand)->toBe(0);
});

it('generates unique handles per store', function () {
    $first = $this->products->create($this->store, ['title' => 'T-Shirt']);
    $second = $this->products->create($this->store, ['title' => 'T-Shirt']);

    expect($first->handle)->toBe('t-shirt')
        ->and($second->handle)->toBe('t-shirt-1');
});

it('activates a draft product only when it has a priced variant', function () {
    $product = $this->products->create($this->store, [
        'title' => 'Priced Product',
        'status' => ProductStatus::Draft,
        'variants' => [['price_amount' => 2500, 'is_default' => true]],
    ]);

    $this->products->transitionStatus($product, ProductStatus::Active);

    expect($product->refresh()->status)->toBe(ProductStatus::Active)
        ->and($product->published_at)->not->toBeNull();
});

it('rejects activation without a priced variant', function () {
    $product = $this->products->create($this->store, [
        'title' => 'Free Product',
        'status' => ProductStatus::Draft,
    ]);

    expect(fn () => $this->products->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class)
        ->and($product->refresh()->status)->toBe(ProductStatus::Draft);
});

it('hard deletes only unreferenced draft products', function () {
    $draft = $this->products->create($this->store, ['title' => 'Draft', 'status' => ProductStatus::Draft]);
    $active = Product::factory()->for($this->store)->create(['status' => ProductStatus::Active]);

    $this->products->delete($draft);

    expect($draft->exists)->toBeFalse()
        ->and(fn () => $this->products->delete($active))->toThrow(InvalidProductDeletionException::class);
});
