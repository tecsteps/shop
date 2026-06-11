<?php

use App\Models\Product;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->search = app(SearchService::class);
});

it('returns suggestions matching prefix', function () {
    Product::factory()->active()->for($this->store)->create(['title' => 'Summer Dress']);
    Product::factory()->active()->for($this->store)->create(['title' => 'Summer Hat']);
    Product::factory()->active()->for($this->store)->create(['title' => 'Winter Coat']);

    $suggestions = $this->search->autocomplete($this->store, 'sum');

    expect($suggestions->pluck('title')->sort()->values()->all())
        ->toBe(['Summer Dress', 'Summer Hat']);
});

it('limits results to configured count', function () {
    foreach (range(1, 20) as $index) {
        Product::factory()->active()->for($this->store)->create(['title' => "Summer Item {$index}"]);
    }

    $suggestions = $this->search->autocomplete($this->store, 'summer', 5);

    expect($suggestions)->toHaveCount(5);
});

it('returns empty for very short prefix', function () {
    Product::factory()->active()->for($this->store)->create(['title' => 'Anorak']);

    $suggestions = $this->search->autocomplete($this->store, 'a');

    expect($suggestions)->toBeEmpty();
});
