<?php

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

it('returns empty collection for a short prefix', function (): void {
    Product::factory()->for($this->store)->create(['title' => 'Amazing Product']);

    $results = app(SearchService::class)->autocomplete($this->store, 'A');

    expect($results)->toHaveCount(0);
});

it('returns matches for a valid prefix', function (): void {
    Product::factory()->for($this->store)->create(['title' => 'Arctic Parka']);
    Product::factory()->for($this->store)->create(['title' => 'Aviator Jacket']);
    Product::factory()->for($this->store)->create(['title' => 'Beach Towel']);

    $results = app(SearchService::class)->autocomplete($this->store, 'Ar');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Arctic Parka');
});
