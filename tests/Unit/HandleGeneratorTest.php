<?php

use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('generates a slug from title', function () {
    $store = Store::factory()->create();

    $handle = app(HandleGenerator::class)->generate('My Amazing Product', 'products', $store->getKey());

    expect($handle)->toBe('my-amazing-product');
});

it('appends suffix on collision', function () {
    $store = Store::factory()->create();
    Product::factory()->for($store)->create(['handle' => 't-shirt']);

    $handle = app(HandleGenerator::class)->generate('T-Shirt', 'products', $store->getKey());

    expect($handle)->toBe('t-shirt-1');
});

it('increments suffix on multiple collisions', function () {
    $store = Store::factory()->create();
    Product::factory()->for($store)->create(['handle' => 't-shirt']);
    Product::factory()->for($store)->create(['handle' => 't-shirt-1']);

    $handle = app(HandleGenerator::class)->generate('T-Shirt', 'products', $store->getKey());

    expect($handle)->toBe('t-shirt-2');
});

it('handles special characters', function () {
    $store = Store::factory()->create();

    $handle = app(HandleGenerator::class)->generate("Loewe's Fall/Winter 2026", 'products', $store->getKey());

    expect($handle)->toMatch('/^[a-z0-9]+(-[a-z0-9]+)*$/');
    expect($handle)->toContain('loewe');
});

it('excludes current record id from collision check', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->for($store)->create(['handle' => 't-shirt']);

    $handle = app(HandleGenerator::class)->generate('T-Shirt', 'products', $store->getKey(), $product->getKey());

    expect($handle)->toBe('t-shirt');
});

it('scopes uniqueness check to store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    Product::factory()->for($storeA)->create(['handle' => 't-shirt']);

    $handle = app(HandleGenerator::class)->generate('T-Shirt', 'products', $storeB->getKey());

    expect($handle)->toBe('t-shirt');
});
