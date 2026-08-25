<?php

use App\Models\Product;
use App\Services\SearchService;

it('returns suggestions matching prefix', function () {
    $ctx = createStoreContext();
    Product::factory()->active()->create(['store_id' => $ctx['store']->id, 'title' => 'Summer Dress']);
    Product::factory()->active()->create(['store_id' => $ctx['store']->id, 'title' => 'Summer Hat']);
    Product::factory()->active()->create(['store_id' => $ctx['store']->id, 'title' => 'Winter Coat']);

    $results = app(SearchService::class)->autocomplete($ctx['store'], 'sum');

    expect($results->count())->toBe(2);
});

it('limits results to configured count', function () {
    $ctx = createStoreContext();
    foreach (range(1, 20) as $i) {
        Product::factory()->active()->create(['store_id' => $ctx['store']->id, 'title' => 'Product '.$i]);
    }

    $results = app(SearchService::class)->autocomplete($ctx['store'], 'Product', 5);

    expect($results->count())->toBe(5);
});
