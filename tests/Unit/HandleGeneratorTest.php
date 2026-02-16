<?php

use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a slug from title', function () {
    $ctx = createStoreContext();
    $handle = HandleGenerator::generate('My Amazing Product', 'products', $ctx['store']->id);
    expect($handle)->toBe('my-amazing-product');
});

it('appends suffix on collision', function () {
    $ctx = createStoreContext();
    \App\Models\Product::factory()->for($ctx['store'])->create(['handle' => 't-shirt']);

    $handle = HandleGenerator::generate('T-Shirt', 'products', $ctx['store']->id);
    expect($handle)->toBe('t-shirt-1');
});

it('increments suffix on multiple collisions', function () {
    $ctx = createStoreContext();
    \App\Models\Product::factory()->for($ctx['store'])->create(['handle' => 't-shirt']);
    \App\Models\Product::factory()->for($ctx['store'])->create(['handle' => 't-shirt-1']);

    $handle = HandleGenerator::generate('T-Shirt', 'products', $ctx['store']->id);
    expect($handle)->toBe('t-shirt-2');
});

it('handles special characters', function () {
    $ctx = createStoreContext();
    $handle = HandleGenerator::generate("Loewe's Fall/Winter 2026", 'products', $ctx['store']->id);
    expect($handle)->toMatch('/^[a-z0-9-]+$/');
});

it('excludes current record id from collision check', function () {
    $ctx = createStoreContext();
    $product = \App\Models\Product::factory()->for($ctx['store'])->create(['handle' => 't-shirt']);

    $handle = HandleGenerator::generate('T-Shirt', 'products', $ctx['store']->id, $product->id);
    expect($handle)->toBe('t-shirt');
});

it('scopes uniqueness check to store', function () {
    $ctx = createStoreContext();
    \App\Models\Product::factory()->for($ctx['store'])->create(['handle' => 't-shirt']);

    $store2 = \App\Models\Store::factory()->for($ctx['org'])->create();
    $handle = HandleGenerator::generate('T-Shirt', 'products', $store2->id);
    expect($handle)->toBe('t-shirt');
});
