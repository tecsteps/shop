<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('indexes a product on create', function (): void {
    $product = Product::factory()->for($this->store)->create([
        'title' => 'Arctic Wool Coat',
    ]);

    $results = app(SearchService::class)->search($this->store, 'Arctic');

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($product->id);
});

it('removes a product from index on delete', function (): void {
    $product = Product::factory()->for($this->store)->create([
        'title' => 'Desert Sandals',
    ]);

    expect(app(SearchService::class)->search($this->store, 'Desert')->total())->toBe(1);

    $product->delete();

    expect(app(SearchService::class)->search($this->store, 'Desert')->total())->toBe(0);
});

it('finds products by title', function (): void {
    Product::factory()->for($this->store)->create(['title' => 'Blue Leather Wallet']);
    Product::factory()->for($this->store)->create(['title' => 'Red Canvas Bag']);

    $results = app(SearchService::class)->search($this->store, 'Leather');

    expect($results->total())->toBe(1)
        ->and($results->first()->title)->toBe('Blue Leather Wallet');
});

it('finds products by partial (prefix) match', function (): void {
    Product::factory()->for($this->store)->create(['title' => 'Mountaineer Jacket']);

    $results = app(SearchService::class)->search($this->store, 'Mount');

    expect($results->total())->toBe(1);
});

it('scopes results to the current store', function (): void {
    $otherStore = Store::factory()->create();

    Product::factory()->for($this->store)->create(['title' => 'Shared Title']);
    Product::factory()->for($otherStore)->create(['title' => 'Shared Title']);

    $results = app(SearchService::class)->search($this->store, 'Shared');

    expect($results->total())->toBe(1)
        ->and($results->first()->store_id)->toBe($this->store->id);
});

it('returns no results for an empty query', function (): void {
    Product::factory()->for($this->store)->create(['title' => 'Anything']);

    $results = app(SearchService::class)->search($this->store, '');

    expect($results->total())->toBe(0);
});

it('excludes non-active products from search results', function (): void {
    Product::factory()->for($this->store)->create([
        'title' => 'Hidden Draft',
        'status' => ProductStatus::Draft->value,
    ]);

    $results = app(SearchService::class)->search($this->store, 'Hidden');

    expect($results->total())->toBe(0);
});
