<?php

use App\Models\Product;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->searchService = app(SearchService::class);
});

it('returns suggestions matching prefix', function () {
    $products = [
        Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => 'Running Shoes Blue',
        ]),
        Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => 'Running Shorts',
        ]),
        Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => 'Yoga Mat',
        ]),
    ];

    foreach ($products as $product) {
        $this->searchService->syncProduct($product);
    }

    $suggestions = $this->searchService->autocomplete($this->store, 'Ru');

    expect($suggestions)->toHaveCount(2)
        ->and($suggestions->toArray())->each->toContain('Running');
});

it('limits results to configured count', function () {
    for ($i = 1; $i <= 10; $i++) {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => "Limited Item $i",
        ]);
        $this->searchService->syncProduct($product);
    }

    $suggestions = $this->searchService->autocomplete($this->store, 'Li', 3);

    expect($suggestions)->toHaveCount(3);
});

it('returns empty for very short prefix', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Short Test',
    ]);
    $this->searchService->syncProduct($product);

    $suggestions = $this->searchService->autocomplete($this->store, 'S');

    expect($suggestions)->toHaveCount(0);
});
