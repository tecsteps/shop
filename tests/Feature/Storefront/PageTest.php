<?php

use App\Enums\PageStatus;
use App\Models\Page;

it('creates a page with store relationship', function () {
    $ctx = createStoreContext();

    $page = Page::factory()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'About Us',
        'handle' => 'about-us',
        'status' => PageStatus::Published,
    ]);

    expect($page->store->id)->toBe($ctx['store']->id)
        ->and($page->title)->toBe('About Us')
        ->and($page->handle)->toBe('about-us')
        ->and($page->status)->toBe(PageStatus::Published);
});

it('scopes pages to current store', function () {
    $ctx = createStoreContext();
    Page::factory()->count(3)->create(['store_id' => $ctx['store']->id]);

    $otherStore = \App\Models\Store::factory()->create(['organization_id' => $ctx['organization']->id]);
    Page::factory()->count(5)->create(['store_id' => $otherStore->id]);

    expect(Page::count())->toBe(3);
});

it('has draft and archived factory states', function () {
    $ctx = createStoreContext();

    $draft = Page::factory()->draft()->create(['store_id' => $ctx['store']->id]);
    $archived = Page::factory()->archived()->create(['store_id' => $ctx['store']->id]);

    expect($draft->status)->toBe(PageStatus::Draft)
        ->and($draft->published_at)->toBeNull()
        ->and($archived->status)->toBe(PageStatus::Archived);
});

it('enforces unique handle per store', function () {
    $ctx = createStoreContext();

    Page::factory()->create([
        'store_id' => $ctx['store']->id,
        'handle' => 'about-us',
    ]);

    Page::factory()->create([
        'store_id' => $ctx['store']->id,
        'handle' => 'about-us',
    ]);
})->throws(\Illuminate\Database\UniqueConstraintViolationException::class);
