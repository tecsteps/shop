<?php

use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(SearchService::class);
});

it('returns autocomplete results with prefix matching', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Running Shoes',
    ]);
    $this->service->syncProduct($product);

    $results = $this->service->autocomplete($this->store, 'Run');

    expect($results)->toHaveCount(1)
        ->and($results->first()['title'])->toBe('Running Shoes');
});

it('limits autocomplete results', function () {
    for ($i = 0; $i < 10; $i++) {
        $product = Product::factory()->active()->create([
            'store_id' => $this->store->id,
            'title' => "Widget Model {$i}",
        ]);
        $this->service->syncProduct($product);
    }

    $results = $this->service->autocomplete($this->store, 'Widget', 3);

    expect($results)->toHaveCount(3);
});

it('returns empty collection for short prefix', function () {
    $results = $this->service->autocomplete($this->store, 'a');

    expect($results)->toBeEmpty();
});
