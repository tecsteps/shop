<?php

use App\Models\Product;
use App\Services\SearchService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->searchService = app(SearchService::class);
});

it('returns suggestions matching prefix', function () {
    $p1 = Product::factory()->active()->for($this->ctx['store'])->create(['title' => 'Summer Dress']);
    $p2 = Product::factory()->active()->for($this->ctx['store'])->create(['title' => 'Summer Hat']);
    $p3 = Product::factory()->active()->for($this->ctx['store'])->create(['title' => 'Winter Coat']);

    $this->searchService->syncProduct($p1);
    $this->searchService->syncProduct($p2);
    $this->searchService->syncProduct($p3);

    $results = $this->searchService->autocomplete($this->ctx['store'], 'sum');

    expect($results)->toHaveCount(2);
});

it('limits results to configured count', function () {
    for ($i = 0; $i < 10; $i++) {
        $p = Product::factory()->active()->for($this->ctx['store'])->create(['title' => "Summer Item $i"]);
        $this->searchService->syncProduct($p);
    }

    $results = $this->searchService->autocomplete($this->ctx['store'], 'sum', 5);

    expect($results->count())->toBeLessThanOrEqual(5);
});

it('returns empty for very short prefix', function () {
    $results = $this->searchService->autocomplete($this->ctx['store'], '');
    expect($results)->toHaveCount(0);
});
