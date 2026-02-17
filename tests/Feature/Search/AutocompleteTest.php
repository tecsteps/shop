<?php

use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $ctx = createStoreContext();
    $this->store = $ctx['store'];
    $this->user = $ctx['user'];
    $this->service = app(SearchService::class);

    DB::statement('CREATE VIRTUAL TABLE IF NOT EXISTS products_fts USING fts5(product_id, store_id, title, description, vendor, product_type, tags)');
});

test('returns suggestions matching prefix', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Beach Hat',
    ]);
    $this->service->syncProduct($product);

    $other = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Winter Gloves',
    ]);
    $this->service->syncProduct($other);

    $suggestions = $this->service->autocomplete($this->store, 'Sum');

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions->first()->title)->toBe('Summer Beach Hat');
});

test('limits results to configured count', function () {
    foreach (range(1, 10) as $i) {
        $product = Product::factory()->active()->create([
            'store_id' => $this->store->id,
            'title' => "Premium Item {$i}",
        ]);
        $this->service->syncProduct($product);
    }

    $suggestions = $this->service->autocomplete($this->store, 'Premium', 3);

    expect($suggestions)->toHaveCount(3);
});

test('returns empty for very short prefix', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Fancy Bag',
    ]);
    $this->service->syncProduct($product);

    $suggestions = $this->service->autocomplete($this->store, 'F');

    expect($suggestions)->toBeEmpty();
});
