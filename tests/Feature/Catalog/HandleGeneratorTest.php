<?php

use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->generator = new HandleGenerator;
});

test('generates slug from title', function () {
    $handle = $this->generator->generate('My Amazing Product', 'products', $this->store->id);

    expect($handle)->toBe('my-amazing-product');
});

test('appends suffix on collision', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'test-product',
    ]);

    $handle = $this->generator->generate('Test Product', 'products', $this->store->id);

    expect($handle)->toBe('test-product-1');
});

test('increments suffix on multiple collisions', function () {
    Product::factory()->create(['store_id' => $this->store->id, 'handle' => 'test-product']);
    Product::factory()->create(['store_id' => $this->store->id, 'handle' => 'test-product-1']);

    $handle = $this->generator->generate('Test Product', 'products', $this->store->id);

    expect($handle)->toBe('test-product-2');
});

test('excludes specific ID when checking collisions', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'test-product',
    ]);

    $handle = $this->generator->generate('Test Product', 'products', $this->store->id, $product->id);

    expect($handle)->toBe('test-product');
});

test('handles same title in different stores', function () {
    $otherStore = Store::factory()->create();

    Product::factory()->create(['store_id' => $otherStore->id, 'handle' => 'test-product']);

    $handle = $this->generator->generate('Test Product', 'products', $this->store->id);

    expect($handle)->toBe('test-product');
});

test('handles empty title gracefully', function () {
    $handle = $this->generator->generate('', 'products', $this->store->id);

    expect($handle)->toBe('item');
});

test('handles special characters in title', function () {
    $handle = $this->generator->generate('Product with $pecial Ch@racters!', 'products', $this->store->id);

    expect($handle)->toBe('product-with-pecial-ch-at-racters');
});
