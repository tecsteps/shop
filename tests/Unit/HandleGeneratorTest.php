<?php

use App\Models\Product;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->generator = new HandleGenerator;
});

it('generates a slug from title', function () {
    $context = createStoreContext();

    $handle = $this->generator->generate('My Amazing Product', 'products', $context['store']->id);

    expect($handle)->toBe('my-amazing-product');
});

it('appends suffix on collision', function () {
    $context = createStoreContext();

    Product::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'title' => 'T-Shirt',
        'handle' => 't-shirt',
        'status' => 'draft',
        'tags' => [],
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $context['store']->id);

    expect($handle)->toBe('t-shirt-1');
});

it('increments suffix on multiple collisions', function () {
    $context = createStoreContext();

    Product::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'title' => 'T-Shirt',
        'handle' => 't-shirt',
        'status' => 'draft',
        'tags' => [],
    ]);

    Product::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'title' => 'T-Shirt 1',
        'handle' => 't-shirt-1',
        'status' => 'draft',
        'tags' => [],
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $context['store']->id);

    expect($handle)->toBe('t-shirt-2');
});

it('handles special characters', function () {
    $context = createStoreContext();

    $handle = $this->generator->generate("Loewe's Fall/Winter 2026", 'products', $context['store']->id);

    expect($handle)->toMatch('/^[a-z0-9\-]+$/');
});

it('excludes current record id from collision check', function () {
    $context = createStoreContext();

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'title' => 'T-Shirt',
        'handle' => 't-shirt',
        'status' => 'draft',
        'tags' => [],
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $context['store']->id, $product->id);

    expect($handle)->toBe('t-shirt');
});

it('scopes uniqueness check to store', function () {
    $context1 = createStoreContext('store1.test');
    $context2 = createStoreContext('store2.test');

    Product::withoutGlobalScopes()->create([
        'store_id' => $context1['store']->id,
        'title' => 'T-Shirt',
        'handle' => 't-shirt',
        'status' => 'draft',
        'tags' => [],
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $context2['store']->id);

    expect($handle)->toBe('t-shirt');
});
