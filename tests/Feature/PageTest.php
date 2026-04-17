<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

it('can create a page', function () {
    $page = Page::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'About Us',
        'handle' => 'about-us',
    ]);

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->title)->toBe('About Us')
        ->and($page->handle)->toBe('about-us')
        ->and($page->status)->toBe(PageStatus::Draft);
});

it('can create a published page', function () {
    $page = Page::factory()->published()->create([
        'store_id' => $this->store->id,
    ]);

    expect($page->status)->toBe(PageStatus::Published)
        ->and($page->published_at)->not->toBeNull();
});

it('can create an archived page', function () {
    $page = Page::factory()->archived()->create([
        'store_id' => $this->store->id,
    ]);

    expect($page->status)->toBe(PageStatus::Archived);
});

it('scopes pages by store', function () {
    $otherStore = Store::factory()->create();

    Page::factory()->create(['store_id' => $this->store->id]);
    Page::factory()->create(['store_id' => $otherStore->id]);

    expect(Page::query()->count())->toBe(1);
});

it('enforces unique handle per store', function () {
    Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about-us',
    ]);

    expect(fn () => Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about-us',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('allows same handle in different stores', function () {
    $otherStore = Store::factory()->create();

    Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about-us',
    ]);

    $page = Page::factory()->create([
        'store_id' => $otherStore->id,
        'handle' => 'about-us',
    ]);

    expect($page)->toBeInstanceOf(Page::class);
});
