<?php

use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('slugs titles and returns the base when no collision exists', function () {
    $store = Store::factory()->create();

    $handle = HandleGenerator::unique(Product::class, (int) $store->getKey(), 'My Product Name');

    expect($handle)->toBe('my-product-name');
});

it('appends incrementing suffixes when base collides', function () {
    $store = Store::factory()->create();

    Product::factory()->create(['store_id' => $store->getKey(), 'handle' => 'shirt']);
    Product::factory()->create(['store_id' => $store->getKey(), 'handle' => 'shirt-2']);

    $handle = HandleGenerator::unique(Product::class, (int) $store->getKey(), 'Shirt');

    expect($handle)->toBe('shirt-3');
});

it('scopes uniqueness per store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Product::factory()->create(['store_id' => $storeA->getKey(), 'handle' => 'same']);

    $handle = HandleGenerator::unique(Product::class, (int) $storeB->getKey(), 'Same');

    expect($handle)->toBe('same');
});

it('ignores the current record when regenerating', function () {
    $store = Store::factory()->create();

    $product = Product::factory()->create(['store_id' => $store->getKey(), 'handle' => 'hoodie']);

    $handle = HandleGenerator::unique(
        Product::class,
        (int) $store->getKey(),
        'hoodie',
        ignoreId: (int) $product->getKey(),
    );

    expect($handle)->toBe('hoodie');
});
