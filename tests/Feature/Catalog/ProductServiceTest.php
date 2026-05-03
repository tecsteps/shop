<?php

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('handle generator creates unique handles per store', function () {
    $store = Store::factory()->create();

    Product::factory()->create([
        'store_id' => $store->getKey(),
        'title' => 'Classic Cotton T-Shirt',
        'handle' => 'classic-cotton-t-shirt',
    ]);

    $handle = app(HandleGenerator::class)->generate('Classic Cotton T-Shirt', 'products', $store->getKey());

    expect($handle)->toBe('classic-cotton-t-shirt-1');
});

test('product service creates products with a default variant and inventory', function () {
    $store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $store);

    $product = app(ProductService::class)->create($store, [
        'title' => 'Merino Crew',
        'price_amount' => 4999,
        'tags' => ['new'],
    ]);

    expect($product->handle)->toBe('merino-crew')
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->price_amount)->toBe(4999)
        ->and($product->variants->first()->inventoryItem)->not->toBeNull();
});

test('product service routes requested active status through lifecycle checks', function () {
    $store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $store);

    expect(fn () => app(ProductService::class)->create($store, [
        'title' => 'Unpriced Product',
        'status' => ProductStatus::Active,
        'price_amount' => 0,
    ]))->toThrow(InvalidProductTransitionException::class);

    $product = app(ProductService::class)->create($store, [
        'title' => 'Priced Product',
        'status' => ProductStatus::Active,
        'price_amount' => 1000,
    ]);

    expect($product->status)->toBe(ProductStatus::Active)
        ->and($product->published_at)->not->toBeNull();
});

test('product service syncs variant option selections', function () {
    $store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $store);

    $product = app(ProductService::class)->create($store, [
        'title' => 'Optioned Product',
        'options' => [
            [
                'name' => 'Size',
                'position' => 0,
                'values' => [
                    ['value' => 'S', 'position' => 0],
                    ['value' => 'M', 'position' => 1],
                ],
            ],
            [
                'name' => 'Color',
                'position' => 1,
                'values' => [
                    ['value' => 'Black', 'position' => 0],
                    ['value' => 'White', 'position' => 1],
                ],
            ],
        ],
        'variants' => [
            [
                'sku' => 'OPT-S-BLK',
                'price_amount' => 1500,
                'status' => VariantStatus::Active,
                'options' => ['Size' => 'S', 'Color' => 'Black'],
            ],
        ],
    ]);

    $variant = $product->variants()->firstOrFail();
    $values = $variant->optionValues()
        ->with('option')
        ->get()
        ->mapWithKeys(fn (ProductOptionValue $value): array => [$value->option->name => $value->value])
        ->all();

    expect($values)->toBe([
        'Size' => 'S',
        'Color' => 'Black',
    ]);
});

test('product status transitions enforce activation preconditions and dispatch events', function () {
    Event::fake([ProductStatusChanged::class]);

    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::factory()->draft()->create([
        'store_id' => $store->getKey(),
        'title' => 'Draft Product',
    ]);

    expect(fn () => app(ProductService::class)->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);

    ProductVariant::factory()->default()->create([
        'product_id' => $product->getKey(),
        'price_amount' => 1200,
    ]);

    app(ProductService::class)->transitionStatus($product->refresh(), ProductStatus::Active);

    expect($product->refresh()->status)->toBe(ProductStatus::Active)
        ->and($product->published_at)->not->toBeNull();

    Event::assertDispatched(ProductStatusChanged::class);
});

test('duplicate non-empty skus are rejected within the same store', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    app(ProductService::class)->create($store, [
        'title' => 'First Product',
        'variants' => [
            ['sku' => 'DUP-1', 'price_amount' => 1000],
        ],
    ]);

    expect(fn () => app(ProductService::class)->create($store, [
        'title' => 'Second Product',
        'variants' => [
            ['sku' => 'DUP-1', 'price_amount' => 1200],
        ],
    ]))->toThrow(RuntimeException::class);
});
