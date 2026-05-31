<?php

use App\Models\Product;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->search = app(SearchService::class);
});

it('returns suggestions matching prefix', function () {
    // Use neutral non-matching tags/vendor so only the title drives the match;
    // the FTS index spans tags and vendor too, and the default factory seeds a
    // random "summer" tag that would otherwise pollute the assertion.
    $neutral = ['tags' => ['winterwear'], 'vendor' => 'Acme', 'product_type' => 'Apparel', 'description_html' => '<p>A product.</p>'];

    Product::factory()->active()->create(['store_id' => $this->store->id, 'title' => 'Summer Dress'] + $neutral);
    Product::factory()->active()->create(['store_id' => $this->store->id, 'title' => 'Summer Hat'] + $neutral);
    Product::factory()->active()->create(['store_id' => $this->store->id, 'title' => 'Winter Coat'] + $neutral);

    $titles = $this->search->autocomplete($this->store, 'sum')->pluck('title');

    expect($titles)->toContain('Summer Dress')
        ->and($titles)->toContain('Summer Hat')
        ->and($titles)->not->toContain('Winter Coat');
});

it('limits results to configured count', function () {
    for ($i = 1; $i <= 20; $i++) {
        Product::factory()->active()->create([
            'store_id' => $this->store->id,
            'title' => "Sunglasses Model {$i}",
        ]);
    }

    $results = $this->search->autocomplete($this->store, 'sunglasses', limit: 5);

    expect($results)->toHaveCount(5);
});

it('returns empty for very short prefix', function () {
    Product::factory()->active()->create(['store_id' => $this->store->id, 'title' => 'Apple Watch']);

    expect($this->search->autocomplete($this->store, 'a'))->toBeEmpty();
});
