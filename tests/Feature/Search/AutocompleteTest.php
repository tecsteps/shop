<?php

use App\Models\Product;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->searchService = app(SearchService::class);
});

it('returns suggestions matching prefix', function () {
    $product1 = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Dress',
        'status' => 'active',
    ]);
    $product2 = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Hat',
        'status' => 'active',
    ]);
    $product3 = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Winter Coat',
        'status' => 'active',
    ]);

    $this->searchService->syncProduct($product1);
    $this->searchService->syncProduct($product2);
    $this->searchService->syncProduct($product3);

    $results = $this->searchService->autocomplete($this->store, 'Sum');

    expect($results)->toHaveCount(2)
        ->and($results->pluck('title')->toArray())->each->toContain('Summer');
});

it('limits results to configured count', function () {
    for ($i = 0; $i < 20; $i++) {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => "Summer Product $i",
            'status' => 'active',
        ]);
        $this->searchService->syncProduct($product);
    }

    $results = $this->searchService->autocomplete($this->store, 'Summer', 5);

    expect($results)->toHaveCount(5);
});

it('returns empty for very short prefix', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Another Product',
        'status' => 'active',
    ]);
    $this->searchService->syncProduct($product);

    $results = $this->searchService->autocomplete($this->store, '', 5);

    expect($results)->toHaveCount(0);
});
