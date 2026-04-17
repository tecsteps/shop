<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
});

test('page belongs to store', function () {
    $page = Page::factory()->create(['store_id' => $this->store->id]);

    expect($page->store->id)->toBe($this->store->id);
});

test('page casts status to enum', function () {
    $page = Page::factory()->published()->create(['store_id' => $this->store->id]);

    expect($page->status)->toBe(PageStatus::Published);
});

test('page factory creates published state', function () {
    $page = Page::factory()->published()->create(['store_id' => $this->store->id]);

    expect($page->status)->toBe(PageStatus::Published)
        ->and($page->published_at)->not->toBeNull();
});

test('page factory creates archived state', function () {
    $page = Page::factory()->archived()->create(['store_id' => $this->store->id]);

    expect($page->status)->toBe(PageStatus::Archived);
});

test('page handle is unique per store', function () {
    Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about',
    ]);

    expect(fn () => Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('same handle allowed for different stores', function () {
    $otherStore = Store::factory()->create();

    $page1 = Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about',
    ]);

    $page2 = Page::factory()->create([
        'store_id' => $otherStore->id,
        'handle' => 'about',
    ]);

    expect($page1->handle)->toBe('about')
        ->and($page2->handle)->toBe('about');
});
