<?php

use App\Models\Product;
use App\Models\Store;
use App\Services\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->generator = new HandleGenerator;
    $this->store = Store::factory()->create();
});

test('generates a slug from title', function () {
    $handle = $this->generator->generate('My Amazing Product', 'products', $this->store->id);

    expect($handle)->toBe('my-amazing-product');
});

test('appends suffix on collision', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 't-shirt',
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $this->store->id);

    expect($handle)->toBe('t-shirt-1');
});

test('increments suffix on multiple collisions', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 't-shirt',
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 't-shirt-1',
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $this->store->id);

    expect($handle)->toBe('t-shirt-2');
});

test('handles special characters', function () {
    $handle = $this->generator->generate("Loewe's Fall/Winter 2026", 'products', $this->store->id);

    expect($handle)->toMatch('/^[a-z0-9-]+$/');
});

test('excludes current record id from collision check', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 't-shirt',
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $this->store->id, $product->id);

    expect($handle)->toBe('t-shirt');
});

test('scopes uniqueness check to store', function () {
    $otherStore = Store::factory()->create();

    Product::factory()->create([
        'store_id' => $otherStore->id,
        'handle' => 't-shirt',
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $this->store->id);

    expect($handle)->toBe('t-shirt');
});
