<?php

use App\Enums\PageStatus;
use App\Models\Page;

it('creates a page with factory', function () {
    $context = createStoreContext();
    $page = Page::factory()->create(['store_id' => $context['store']->id]);

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->store_id)->toBe($context['store']->id)
        ->and($page->status)->toBe(PageStatus::Draft);
});

it('creates a published page', function () {
    $context = createStoreContext();
    $page = Page::factory()->published()->create(['store_id' => $context['store']->id]);

    expect($page->status)->toBe(PageStatus::Published)
        ->and($page->published_at)->not->toBeNull();
});

it('belongs to store', function () {
    $context = createStoreContext();
    $page = Page::factory()->create(['store_id' => $context['store']->id]);

    expect($page->store->id)->toBe($context['store']->id);
});

it('casts status to enum', function () {
    $context = createStoreContext();
    $page = Page::factory()->create(['store_id' => $context['store']->id, 'status' => 'published']);

    expect($page->status)->toBe(PageStatus::Published);
});

it('enforces unique handle per store', function () {
    $context = createStoreContext();
    Page::factory()->create(['store_id' => $context['store']->id, 'handle' => 'about']);

    Page::factory()->create(['store_id' => $context['store']->id, 'handle' => 'about']);
})->throws(\Illuminate\Database\UniqueConstraintViolationException::class);
