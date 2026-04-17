<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a page via factory', function (): void {
    $page = Page::factory()->create(['store_id' => $this->store->id]);

    expect($page->exists)->toBeTrue()
        ->and($page->status)->toBe(PageStatus::Published)
        ->and($page->store_id)->toBe($this->store->id);
});

it('scopes pages to the current store', function (): void {
    $storeA = $this->store;
    $storeB = Store::factory()->create();

    Page::factory()->create(['store_id' => $storeA->id]);

    app()->instance('current_store', $storeB);
    Page::factory()->create(['store_id' => $storeB->id]);

    expect(Page::count())->toBe(1);

    app()->instance('current_store', $storeA);
    expect(Page::count())->toBe(1);
});

it('enforces unique handle per store', function (): void {
    Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about-us',
    ]);

    expect(fn () => Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about-us',
    ]))->toThrow(QueryException::class);
});

it('allows the same handle across different stores', function (): void {
    $storeB = Store::factory()->create();

    Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about-us',
    ]);

    app()->instance('current_store', $storeB);
    $page = Page::factory()->create([
        'store_id' => $storeB->id,
        'handle' => 'about-us',
    ]);

    expect($page->exists)->toBeTrue();
});
