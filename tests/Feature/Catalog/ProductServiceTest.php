<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeStore(): Store
{
    return Store::factory()->create();
}

it('creates a product with default variant and inventory item', function () {
    $store = makeStore();
    $service = app(ProductService::class);

    $product = $service->create((int) $store->getKey(), [
        'title' => 'Awesome Tee',
        'price_amount' => 2500,
        'quantity_on_hand' => 10,
    ]);

    expect($product->title)->toBe('Awesome Tee')
        ->and($product->handle)->toBe('awesome-tee')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->variants()->count())->toBe(1);

    $variant = $product->variants()->first();

    expect($variant->is_default)->toBeTrue()
        ->and($variant->price_amount)->toBe(2500)
        ->and($variant->inventoryItem)->not->toBeNull()
        ->and($variant->inventoryItem->quantity_on_hand)->toBe(10);
});

it('enforces handle uniqueness per store by appending suffix', function () {
    $store = makeStore();
    $service = app(ProductService::class);

    $first = $service->create((int) $store->getKey(), ['title' => 'Same Name', 'price_amount' => 100]);
    $second = $service->create((int) $store->getKey(), ['title' => 'Same Name', 'price_amount' => 100]);

    expect($first->handle)->toBe('same-name')
        ->and($second->handle)->toBe('same-name-2');
});

it('transitions draft to active when variant has price and title present', function () {
    $store = makeStore();
    $service = app(ProductService::class);

    $product = $service->create((int) $store->getKey(), ['title' => 'Buyable', 'price_amount' => 999]);

    $updated = $service->transitionStatus($product, ProductStatus::Active);

    expect($updated->status)->toBe(ProductStatus::Active)
        ->and($updated->published_at)->not->toBeNull();
});

it('refuses to activate a product without a priced variant', function () {
    $store = makeStore();
    $service = app(ProductService::class);

    $product = $service->create((int) $store->getKey(), ['title' => 'Free', 'price_amount' => 0]);

    $service->transitionStatus($product, ProductStatus::Active);
})->throws(InvalidProductTransitionException::class);

it('refuses to revert active back to draft when stock is ok but no order references is allowed', function () {
    $store = makeStore();
    $service = app(ProductService::class);

    $product = $service->create((int) $store->getKey(), ['title' => 'Reversible', 'price_amount' => 300]);
    $service->transitionStatus($product, ProductStatus::Active);

    $reverted = $service->transitionStatus($product->fresh(), ProductStatus::Draft);

    expect($reverted->status)->toBe(ProductStatus::Draft);
});

it('is idempotent when transitioning to the current status', function () {
    $store = makeStore();
    $service = app(ProductService::class);

    $product = $service->create((int) $store->getKey(), ['title' => 'Stays', 'price_amount' => 100]);

    $same = $service->transitionStatus($product, ProductStatus::Draft);

    expect($same->status)->toBe(ProductStatus::Draft);
});

it('deletes only draft products', function () {
    $store = makeStore();
    $service = app(ProductService::class);

    $product = $service->create((int) $store->getKey(), ['title' => 'Erasable', 'price_amount' => 100]);

    $service->delete($product);

    expect(Product::query()->where('id', $product->getKey())->exists())->toBeFalse();
});

it('refuses to delete an active product', function () {
    $store = makeStore();
    $service = app(ProductService::class);

    $product = $service->create((int) $store->getKey(), ['title' => 'Active One', 'price_amount' => 500]);
    $service->transitionStatus($product, ProductStatus::Active);

    $service->delete($product);
})->throws(RuntimeException::class);

it('updates a product and keeps the variant intact', function () {
    $store = makeStore();
    $service = app(ProductService::class);

    $product = $service->create((int) $store->getKey(), ['title' => 'Original', 'price_amount' => 100]);

    $updated = $service->update($product, ['title' => 'Renamed']);

    expect($updated->title)->toBe('Renamed')
        ->and($updated->variants()->count())->toBe(1);
});
