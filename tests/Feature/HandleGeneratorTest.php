<?php

use App\Models\Product;
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

    Product::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 't-shirt',
    ]);

    $handle = $generator->generate('T-Shirt', 'products', $context['store']->id);

    expect($handle)->toBe('t-shirt-1');
});

it('increments suffix on multiple collisions', function () {
    $context = createStoreContext();
    $generator = new HandleGenerator;

    Product::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 't-shirt',
    ]);
    Product::factory()->create([
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

    expect($handle)->toBe('loewes-fallwinter-2026');
});

it('excludes current record id from collision check', function () {
    $context = createStoreContext();
    $generator = new HandleGenerator;

    $product = Product::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 't-shirt',
    ]);

    $handle = $generator->generate('T-Shirt', 'products', $context['store']->id, $product->id);

    expect($handle)->toBe('t-shirt');
});

it('scopes uniqueness check to store', function () {
    $context = createStoreContext();
    $generator = new HandleGenerator;

    Product::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 't-shirt',
    ]);

    $orgB = \App\Models\Organization::factory()->create();
    $storeB = \App\Models\Store::factory()->create(['organization_id' => $orgB->id]);

    $handle = $generator->generate('T-Shirt', 'products', $storeB->id);

    expect($handle)->toBe('t-shirt');
});
