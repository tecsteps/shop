<?php

use App\Support\HandleGenerator;

it('generates a slug from title', function () {
    $context = createStoreContext();
    $generator = new HandleGenerator;

    $handle = $generator->generate('My Amazing Product', 'products', $context['store']->id);

    expect($handle)->toBe('my-amazing-product');
});

it('appends suffix on collision', function () {
    $context = createStoreContext();
    $generator = new HandleGenerator;

    \App\Models\Product::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 't-shirt',
    ]);

    $handle = $generator->generate('T-Shirt', 'products', $context['store']->id);

    expect($handle)->toBe('t-shirt-1');
});

it('increments suffix on multiple collisions', function () {
    $context = createStoreContext();
    $generator = new HandleGenerator;

    \App\Models\Product::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 't-shirt',
    ]);
    \App\Models\Product::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 't-shirt-1',
    ]);

    $handle = $generator->generate('T-Shirt', 'products', $context['store']->id);

    expect($handle)->toBe('t-shirt-2');
});

it('handles special characters', function () {
    $context = createStoreContext();
    $generator = new HandleGenerator;

    $handle = $generator->generate("Loewe's Fall/Winter 2026", 'products', $context['store']->id);

    expect($handle)->toMatch('/^[a-z0-9\-]+$/');
});

it('excludes current record id from collision check', function () {
    $context = createStoreContext();
    $generator = new HandleGenerator;

    $product = \App\Models\Product::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 't-shirt',
    ]);

    $handle = $generator->generate('T-Shirt', 'products', $context['store']->id, $product->id);

    expect($handle)->toBe('t-shirt');
});

it('scopes uniqueness check to store', function () {
    $contextA = createStoreContext();

    \App\Models\Product::factory()->create([
        'store_id' => $contextA['store']->id,
        'handle' => 't-shirt',
    ]);

    $contextB = createStoreContext();
    $generator = new HandleGenerator;

    $handle = $generator->generate('T-Shirt', 'products', $contextB['store']->id);

    expect($handle)->toBe('t-shirt');
});
