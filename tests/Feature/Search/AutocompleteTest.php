<?php

use App\Models\Product;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(SearchService::class);
});

it('returns prefix matches for autocomplete', function () {
    $summer1 = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Dress',
    ]);
    $this->service->syncProduct($summer1);

    $summer2 = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Shorts',
    ]);
    $this->service->syncProduct($summer2);

    $winter = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Winter Coat',
    ]);
    $this->service->syncProduct($winter);

    $results = $this->service->autocomplete($this->store, 'sum');

    expect($results)->toHaveCount(2)
        ->and($results->pluck('title')->toArray())->each->toContain('Summer');
});

it('respects the limit parameter', function () {
    foreach (range(1, 10) as $i) {
        $product = Product::factory()->active()->create([
            'store_id' => $this->store->id,
            'title' => "Alpha Product {$i}",
        ]);
        $this->service->syncProduct($product);
    }

    $results = $this->service->autocomplete($this->store, 'alpha', 5);

    expect($results)->toHaveCount(5);
});

it('returns empty for very short prefixes', function () {
    Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Apple Watch',
    ]);

    $results = $this->service->autocomplete($this->store, 'a');

    expect($results)->toBeEmpty();
});
