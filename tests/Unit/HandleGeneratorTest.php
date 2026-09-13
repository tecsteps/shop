<?php

use App\Models\Product;
use App\Support\HandleGenerator;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('generates a slug from the title', function () {
    expect(app(HandleGenerator::class)->generate('My Amazing Product', 'products', 1))->toBe('my-amazing-product');
});

it('appends a suffix on collision', function () {
    ['store' => $store] = $this->createStoreContext();
    Product::factory()->create(['store_id' => $store->id, 'handle' => 't-shirt']);

    expect(app(HandleGenerator::class)->generate('T-Shirt', 'products', $store->id))->toBe('t-shirt-1');
});

it('increments the suffix on multiple collisions', function () {
    ['store' => $store] = $this->createStoreContext();
    Product::factory()->create(['store_id' => $store->id, 'handle' => 't-shirt']);
    Product::factory()->create(['store_id' => $store->id, 'handle' => 't-shirt-1']);

    expect(app(HandleGenerator::class)->generate('T-Shirt', 'products', $store->id))->toBe('t-shirt-2');
});

it('handles special characters', function () {
    $handle = app(HandleGenerator::class)->generate("Loewe's Fall/Winter 2026", 'products', 1);

    expect($handle)->toMatch('/^[a-z0-9-]+$/');
    expect($handle)->not->toContain("'", '/');
});

it('excludes the current record id from collision checks', function () {
    ['store' => $store] = $this->createStoreContext();
    $product = Product::factory()->create(['store_id' => $store->id, 'handle' => 't-shirt']);

    expect(app(HandleGenerator::class)->generate('T-Shirt', 'products', $store->id, $product->id))->toBe('t-shirt');
});

it('scopes uniqueness checks to the store', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    Product::factory()->create(['store_id' => $storeA->id, 'handle' => 't-shirt']);

    expect(app(HandleGenerator::class)->generate('T-Shirt', 'products', $storeB->id))->toBe('t-shirt');
});
