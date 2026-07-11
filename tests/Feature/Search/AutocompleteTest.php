<?php

use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('autocomplete uses safe prefix matching and respects its limit', function () {
    $store = Store::factory()->create();
    Product::factory()->count(3)->for($store)->sequence(
        ['title' => 'Running Shirt'],
        ['title' => 'Running Shoes'],
        ['title' => 'Running Shorts'],
    )->create();

    $results = app(SearchService::class)->autocomplete($store, 'running sh"***', 2);

    expect($results)->toHaveCount(2)
        ->and($results->every(fn (Product $product): bool => str_starts_with($product->title, 'Running Sh')))->toBeTrue();
});
