<?php

use App\Models\Product;
use App\Support\HandleGenerator;

it('generates a slug from title', function () {
    $gen = new HandleGenerator;

    expect($gen->generate('My Amazing Product', 'products', 1))->toBe('my-amazing-product');
});

it('appends suffix on collision', function () {
    $ctx = createStoreContext();
    Product::factory()->create(['store_id' => $ctx['store']->id, 'title' => 'T-Shirt', 'handle' => 't-shirt']);

    $gen = new HandleGenerator;

    expect($gen->generate('T-Shirt', 'products', $ctx['store']->id))->toBe('t-shirt-1');
});

it('increments suffix on multiple collisions', function () {
    $ctx = createStoreContext();
    Product::factory()->create(['store_id' => $ctx['store']->id, 'title' => 'T-Shirt', 'handle' => 't-shirt']);
    Product::factory()->create(['store_id' => $ctx['store']->id, 'title' => 'T-Shirt', 'handle' => 't-shirt-1']);

    $gen = new HandleGenerator;

    expect($gen->generate('T-Shirt', 'products', $ctx['store']->id))->toBe('t-shirt-2');
});

it('handles special characters', function () {
    $gen = new HandleGenerator;
    $handle = $gen->generate("Loewe's Fall/Winter 2026", 'products', 1);

    expect($handle)->toMatch('/^[a-z0-9-]+$/');
});

it('excludes current record id from collision check', function () {
    $ctx = createStoreContext();
    $product = Product::factory()->create(['store_id' => $ctx['store']->id, 'title' => 'T-Shirt', 'handle' => 't-shirt']);

    $gen = new HandleGenerator;

    expect($gen->generate('T-Shirt', 'products', $ctx['store']->id, $product->id))->toBe('t-shirt');
});

it('scopes uniqueness check to store', function () {
    $ctx = createStoreContext();
    Product::factory()->create(['store_id' => $ctx['store']->id, 'title' => 'T-Shirt', 'handle' => 't-shirt']);

    $gen = new HandleGenerator;

    expect($gen->generate('T-Shirt', 'products', 999))->toBe('t-shirt');
});
