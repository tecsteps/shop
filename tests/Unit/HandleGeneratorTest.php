<?php

use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->generator = app(HandleGenerator::class);
});

it('generates a slug from a title', function () {
    expect($this->generator->generate('My Amazing Product', 'products', $this->store->id))
        ->toBe('my-amazing-product');
});

it('increments a suffix for collisions in the same store', function () {
    Product::factory()->for($this->store)->create(['handle' => 't-shirt']);
    Product::factory()->for($this->store)->create(['handle' => 't-shirt-1']);

    expect($this->generator->generate('T-Shirt', 'products', $this->store->id))
        ->toBe('t-shirt-2');
});

it('normalizes special characters into a valid handle', function () {
    $handle = $this->generator->generate("Loewe's Fall/Winter 2026", 'products', $this->store->id);

    expect($handle)->toMatch('/^[a-z0-9]+(?:-[a-z0-9]+)*$/');
});

it('excludes the current record and scopes collisions to a store', function () {
    $product = Product::factory()->for($this->store)->create(['handle' => 't-shirt']);
    $otherStore = Store::factory()->create();

    expect($this->generator->generate('T-Shirt', 'products', $this->store->id, $product->id))
        ->toBe('t-shirt')
        ->and($this->generator->generate('T-Shirt', 'products', $otherStore->id))
        ->toBe('t-shirt');
});
