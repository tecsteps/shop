<?php

use App\Models\Product;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('it slugifies the title', function () {
    $store = $this->createStore();

    $handle = HandleGenerator::generate('My Cool Product!', 'products', $store->id);

    expect($handle)->toBe('my-cool-product');
});

test('it appends an incrementing suffix on collisions', function () {
    $store = $this->createStore();

    Product::factory()->create(['store_id' => $store->id, 'handle' => 'my-product']);
    Product::factory()->create(['store_id' => $store->id, 'handle' => 'my-product-1']);

    $handle = HandleGenerator::generate('My Product', 'products', $store->id);

    expect($handle)->toBe('my-product-2');
});

test('handles are unique per store, not globally', function () {
    $storeA = $this->createStore();
    $storeB = $this->createStore();

    Product::factory()->create(['store_id' => $storeA->id, 'handle' => 'my-product']);

    expect(HandleGenerator::generate('My Product', 'products', $storeB->id))->toBe('my-product');
});

test('excludeId ignores the given record, e.g. when updating itself', function () {
    $store = $this->createStore();

    $product = Product::factory()->create(['store_id' => $store->id, 'handle' => 'my-product']);

    expect(HandleGenerator::generate('My Product', 'products', $store->id, $product->id))->toBe('my-product');
});

test('it falls back to a default slug when the title has no slug characters', function () {
    $store = $this->createStore();

    expect(HandleGenerator::generate('!!!', 'products', $store->id))->toBe('untitled');
});
