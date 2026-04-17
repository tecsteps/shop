<?php

use App\Models\Product;
use App\Support\HandleGenerator;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->generator = new HandleGenerator;
    $this->ctx = createStoreContext();
});

it('generates a slug from title', function () {
    $handle = $this->generator->generate('Summer T-Shirt', 'products', $this->ctx['store']->id);

    expect($handle)->toBe('summer-t-shirt');
});

it('appends suffix on collision', function () {
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'handle' => 'summer-t-shirt',
    ]);

    $handle = $this->generator->generate('Summer T-Shirt', 'products', $this->ctx['store']->id);

    expect($handle)->toBe('summer-t-shirt-1');
});

it('increments suffix on multiple collisions', function () {
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'handle' => 'summer-t-shirt',
    ]);
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'handle' => 'summer-t-shirt-1',
    ]);

    $handle = $this->generator->generate('Summer T-Shirt', 'products', $this->ctx['store']->id);

    expect($handle)->toBe('summer-t-shirt-2');
});

it('handles special characters', function () {
    $handle = $this->generator->generate('Fancy & Elegant: "Product"!', 'products', $this->ctx['store']->id);

    expect($handle)->toBe('fancy-elegant-product');
});

it('excludes current record id from collision check', function () {
    $product = Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'handle' => 'summer-t-shirt',
    ]);

    $handle = $this->generator->generate('Summer T-Shirt', 'products', $this->ctx['store']->id, $product->id);

    expect($handle)->toBe('summer-t-shirt');
});

it('scopes uniqueness check to store', function () {
    $otherStore = \App\Models\Store::factory()->create();

    Product::factory()->create([
        'store_id' => $otherStore->id,
        'handle' => 'summer-t-shirt',
    ]);

    $handle = $this->generator->generate('Summer T-Shirt', 'products', $this->ctx['store']->id);

    expect($handle)->toBe('summer-t-shirt');
});
