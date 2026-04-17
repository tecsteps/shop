<?php

use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->generator = new HandleGenerator;
});

it('generates a slug from title', function () {
    $handle = $this->generator->generate('My Cool Product', 'products', $this->store->id);

    expect($handle)->toBe('my-cool-product');
});

it('appends a suffix on collision', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'widget',
    ]);

    $handle = $this->generator->generate('Widget', 'products', $this->store->id);

    expect($handle)->toBe('widget-1');
});

it('increments suffix on multiple collisions', function () {
    Product::factory()->create(['store_id' => $this->store->id, 'handle' => 'gadget']);
    Product::factory()->create(['store_id' => $this->store->id, 'handle' => 'gadget-1']);

    $handle = $this->generator->generate('Gadget', 'products', $this->store->id);

    expect($handle)->toBe('gadget-2');
});

it('excludes current record when regenerating', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'existing-product',
    ]);

    $handle = $this->generator->generate(
        'Existing Product',
        'products',
        $this->store->id,
        $product->id
    );

    expect($handle)->toBe('existing-product');
});

it('handles empty title gracefully', function () {
    $handle = $this->generator->generate('', 'products', $this->store->id);

    expect($handle)->toBe('item');
});

it('handles special characters in title', function () {
    $handle = $this->generator->generate('Product @#$% Special!', 'products', $this->store->id);

    expect($handle)->toBe('product-at-special');
});

it('scopes uniqueness to store', function () {
    $otherStore = Store::factory()->create();

    Product::factory()->create(['store_id' => $otherStore->id, 'handle' => 'shared-name']);

    $handle = $this->generator->generate('Shared Name', 'products', $this->store->id);

    expect($handle)->toBe('shared-name');
});
