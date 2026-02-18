<?php

use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->generator = new HandleGenerator;
});

it('generates a slug from title', function () {
    $ctx = createStoreContext();

    $handle = $this->generator->generate('My Amazing Product', 'products', $ctx['store']->id);

    expect($handle)->toBe('my-amazing-product');
});

it('appends suffix on collision', function () {
    $ctx = createStoreContext();

    Product::factory()->create([
        'store_id' => $ctx['store']->id,
        'handle' => 't-shirt',
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $ctx['store']->id);

    expect($handle)->toBe('t-shirt-1');
});

it('increments suffix on multiple collisions', function () {
    $ctx = createStoreContext();

    Product::factory()->create(['store_id' => $ctx['store']->id, 'handle' => 't-shirt']);
    Product::factory()->create(['store_id' => $ctx['store']->id, 'handle' => 't-shirt-1']);

    $handle = $this->generator->generate('T-Shirt', 'products', $ctx['store']->id);

    expect($handle)->toBe('t-shirt-2');
});

it('handles special characters', function () {
    $ctx = createStoreContext();

    $handle = $this->generator->generate("Loewe's Fall/Winter 2026", 'products', $ctx['store']->id);

    expect($handle)->toMatch('/^[a-z0-9\-]+$/')
        ->and($handle)->toContain('loewe');
});

it('excludes current record id from collision check', function () {
    $ctx = createStoreContext();

    $product = Product::factory()->create([
        'store_id' => $ctx['store']->id,
        'handle' => 't-shirt',
        'title' => 'T-Shirt',
    ]);

    $handle = $this->generator->generate('T-Shirt', 'products', $ctx['store']->id, $product->id);

    expect($handle)->toBe('t-shirt');
});

it('scopes uniqueness check to store', function () {
    $ctx = createStoreContext();

    Product::factory()->create([
        'store_id' => $ctx['store']->id,
        'handle' => 't-shirt',
    ]);

    $storeB = Store::factory()->create(['organization_id' => $ctx['organization']->id]);

    $handle = $this->generator->generate('T-Shirt', 'products', $storeB->id);

    expect($handle)->toBe('t-shirt');
});
