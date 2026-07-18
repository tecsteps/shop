<?php

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scopes products to the current store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    $productA = Product::factory()->create([
        'store_id' => $storeA->id,
        'title' => 'Store A Product',
    ]);
    Product::factory()->create([
        'store_id' => $storeB->id,
        'title' => 'Store B Product',
    ]);

    app()->instance('current_store', $storeA);

    $products = Product::query()->get();

    expect($products)->toHaveCount(1)
        ->and($products->first()->is($productA))->toBeTrue();
});

it('auto assigns store_id when creating within store context', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::query()->create([
        'title' => 'Scoped Product',
        'handle' => 'scoped-product',
        'tags' => [],
    ]);

    expect($product->store_id)->toBe($store->id);
});
