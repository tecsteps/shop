<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\SearchService;

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
    $this->search = app(SearchService::class);
});

it('returns products matching search query', function (): void {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Cotton T-Shirt',
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Red Wool Sweater',
        'status' => ProductStatus::Active,
    ]);

    $results = $this->search->search($this->store, 'cotton');

    expect($results->total())->toBe(1);
    expect($results->items()[0]->title)->toBe('Blue Cotton T-Shirt');
});

it('scopes search to the current store', function (): void {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Store A T-Shirt',
        'status' => ProductStatus::Active,
    ]);

    $other = $this->createStoreContext(['hostname' => 'other-store.test']);
    Product::factory()->create([
        'store_id' => $other['store']->id,
        'title' => 'Store B T-Shirt Deluxe',
        'status' => ProductStatus::Active,
    ]);

    app()->instance('current_store', $this->store->fresh());
    $results = $this->search->search($this->store, 't-shirt');

    expect($results->total())->toBe(1);
    expect($results->items()[0]->title)->toBe('Store A T-Shirt');
});

it('returns empty for no matches', function (): void {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Cotton T-Shirt',
        'status' => ProductStatus::Active,
    ]);

    $results = $this->search->search($this->store, 'xyznonexistent');

    expect($results->total())->toBe(0);
});

it('logs search query for analytics', function (): void {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Cotton T-Shirt',
        'status' => ProductStatus::Active,
    ]);

    $this->search->search($this->store, 'cotton');

    $row = \DB::table('search_queries')->where('store_id', $this->store->id)->first();
    expect($row)->not->toBeNull();
    expect($row->query)->toBe('cotton');
    expect((int) $row->results_count)->toBe(1);
});

it('paginates search results', function (): void {
    for ($i = 1; $i <= 25; $i++) {
        Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => "Cotton Tee {$i}",
            'status' => ProductStatus::Active,
        ]);
    }

    $page1 = $this->search->search($this->store, 'cotton', [], 12, 1);
    $page2 = $this->search->search($this->store, 'cotton', [], 12, 2);
    $page3 = $this->search->search($this->store, 'cotton', [], 12, 3);

    expect($page1->total())->toBe(25);
    expect($page1->count())->toBe(12);
    expect($page2->count())->toBe(12);
    expect($page3->count())->toBe(1);
});

it('excludes non-active products from search results', function (): void {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Active Cotton Tee',
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Draft Cotton Tee',
        'status' => ProductStatus::Draft,
    ]);

    $results = $this->search->search($this->store, 'cotton');

    expect($results->total())->toBe(1);
    expect($results->items()[0]->title)->toBe('Active Cotton Tee');
});

it('removes product from index on delete', function (): void {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Gone Soon Cotton Tee',
        'status' => ProductStatus::Active,
    ]);

    expect(\DB::table('products_fts')->where('product_id', $product->id)->exists())->toBeTrue();

    $product->delete();

    expect(\DB::table('products_fts')->where('product_id', $product->id)->exists())->toBeFalse();
});
