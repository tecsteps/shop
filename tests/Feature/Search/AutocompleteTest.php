<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\SearchService;

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
    $this->search = app(SearchService::class);
});

it('returns suggestions matching prefix', function (): void {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Dress',
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Hat',
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Winter Coat',
        'status' => ProductStatus::Active,
    ]);

    $results = $this->search->autocomplete($this->store, 'sum', 8);

    expect($results)->toHaveCount(2);
    $titles = $results->pluck('title')->all();
    expect($titles)->toContain('Summer Dress');
    expect($titles)->toContain('Summer Hat');
});

it('limits results to the configured count', function (): void {
    for ($i = 1; $i <= 20; $i++) {
        Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => "Matchable Product {$i}",
            'status' => ProductStatus::Active,
        ]);
    }

    $results = $this->search->autocomplete($this->store, 'matchable', 5);

    expect($results)->toHaveCount(5);
});

it('returns empty for very short prefix', function (): void {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Apples',
        'status' => ProductStatus::Active,
    ]);

    $results = $this->search->autocomplete($this->store, 'a', 8);

    expect($results)->toBeEmpty();
});

it('scopes autocomplete suggestions to the current store', function (): void {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Dress',
        'status' => ProductStatus::Active,
    ]);

    $other = $this->createStoreContext(['hostname' => 'other.test']);
    Product::factory()->create([
        'store_id' => $other['store']->id,
        'title' => 'Summer Deluxe Bag',
        'status' => ProductStatus::Active,
    ]);

    app()->instance('current_store', $this->store->fresh());
    $results = $this->search->autocomplete($this->store, 'sum', 8);

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Summer Dress');
});
