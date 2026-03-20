<?php

use App\Enums\PageStatus;
use App\Models\Page;

beforeEach(function () {
    $this->context = createStoreContext();
});

it('creates a page with factory', function () {
    $page = Page::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->store_id)->toBe($this->context['store']->id)
        ->and($page->status)->toBe(PageStatus::Published);
});

it('creates a draft page', function () {
    $page = Page::factory()->draft()->create([
        'store_id' => $this->context['store']->id,
    ]);

    expect($page->status)->toBe(PageStatus::Draft)
        ->and($page->published_at)->toBeNull();
});

it('creates an archived page', function () {
    $page = Page::factory()->archived()->create([
        'store_id' => $this->context['store']->id,
    ]);

    expect($page->status)->toBe(PageStatus::Archived);
});

it('has a store relationship', function () {
    $page = Page::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    expect($page->store->id)->toBe($this->context['store']->id);
});

it('enforces unique handle per store', function () {
    Page::factory()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'about',
    ]);

    Page::factory()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'about',
    ]);
})->throws(\Illuminate\Database\UniqueConstraintViolationException::class);

it('allows same handle in different stores', function () {
    Page::factory()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'about',
    ]);

    $otherStore = \App\Models\Store::factory()->create();
    $page = Page::factory()->create([
        'store_id' => $otherStore->id,
        'handle' => 'about',
    ]);

    expect($page->handle)->toBe('about');
});

it('scopes pages to current store', function () {
    Page::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    $otherStore = \App\Models\Store::factory()->create();
    Page::factory()->create([
        'store_id' => $otherStore->id,
    ]);

    expect(Page::count())->toBe(1);
});
