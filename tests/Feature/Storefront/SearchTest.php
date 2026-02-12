<?php

use App\Livewire\Storefront\Search\Index;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'storefront-search.test',
    ]);
    app()->instance('current_store', $this->store);
});

test('search page renders', function () {
    Livewire::test(Index::class)
        ->assertStatus(200)
        ->assertSee('Search');
});

test('search with query returns results', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Vintage Denim Jacket',
        'status' => 'active',
    ]);

    Livewire::test(Index::class)
        ->set('q', 'Vintage')
        ->assertSee('Vintage Denim Jacket');
});

test('search with no results shows empty state', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Silk Scarf',
        'status' => 'active',
    ]);

    Livewire::test(Index::class)
        ->set('q', 'xyznonexistent')
        ->assertSee('No results found');
});
