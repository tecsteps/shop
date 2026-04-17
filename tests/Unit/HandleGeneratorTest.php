<?php

use App\Models\Organization;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->generator = new HandleGenerator;
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

it('generates a slug from title', function () {
    $handle = $this->generator->generate('My Great Product', 'products', $this->store->id);

    expect($handle)->toBe('my-great-product');
});

it('appends suffix on collision', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'existing-product',
    ]);

    $handle = $this->generator->generate('Existing Product', 'products', $this->store->id);

    expect($handle)->toBe('existing-product-1');
});

it('increments suffix on multiple collisions', function () {
    Product::factory()->create(['store_id' => $this->store->id, 'handle' => 'duplicate']);
    Product::factory()->create(['store_id' => $this->store->id, 'handle' => 'duplicate-1']);
    Product::factory()->create(['store_id' => $this->store->id, 'handle' => 'duplicate-2']);

    $handle = $this->generator->generate('Duplicate', 'products', $this->store->id);

    expect($handle)->toBe('duplicate-3');
});

it('handles special characters', function () {
    $handle = $this->generator->generate('Hello!!! World*** 123', 'products', $this->store->id);

    expect($handle)->toBe('hello-world-123');
});

it('excludes current record when updating', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'my-product',
    ]);

    $handle = $this->generator->generate('My Product', 'products', $this->store->id, $product->id);

    expect($handle)->toBe('my-product');
});

it('scopes handle uniqueness to store', function () {
    $org = Organization::factory()->create();
    $otherStore = Store::factory()->for($org)->create();

    Product::factory()->create([
        'store_id' => $otherStore->id,
        'handle' => 'shared-handle',
    ]);

    $handle = $this->generator->generate('Shared Handle', 'products', $this->store->id);

    expect($handle)->toBe('shared-handle');
});
