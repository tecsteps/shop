<?php

use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->generator = new HandleGenerator;
});

it('generates a slug from title', function () {
    $store = Store::factory()->create();

    $handle = $this->generator->generate('My Amazing Product', 'products', $store->id);

    expect($handle)->toBe('my-amazing-product');
});

it('appends suffix on collision', function () {
    $store = Store::factory()->create();
    Product::factory()->create(['store_id' => $store->id, 'handle' => 't-shirt']);

    $handle = $this->generator->generate('T-Shirt', 'products', $store->id);

    expect($handle)->toBe('t-shirt-1');
});

it('increments suffix on multiple collisions', function () {
    $store = Store::factory()->create();
    Product::factory()->create(['store_id' => $store->id, 'handle' => 't-shirt']);
    Product::factory()->create(['store_id' => $store->id, 'handle' => 't-shirt-1']);

    $handle = $this->generator->generate('T-Shirt', 'products', $store->id);

    expect($handle)->toBe('t-shirt-2');
});

it('handles special characters', function () {
    $store = Store::factory()->create();

    $handle = $this->generator->generate("Loewe's Fall/Winter 2026", 'products', $store->id);

    expect($handle)->toBe('loewes-fallwinter-2026');
});

it('excludes current record id from collision check', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id, 'handle' => 't-shirt']);

    $handle = $this->generator->generate('T-Shirt', 'products', $store->id, $product->id);

    expect($handle)->toBe('t-shirt');
});

it('scopes uniqueness check to store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    Product::factory()->create(['store_id' => $storeA->id, 'handle' => 't-shirt']);

    $handle = $this->generator->generate('T-Shirt', 'products', $storeB->id);

    expect($handle)->toBe('t-shirt');
});
