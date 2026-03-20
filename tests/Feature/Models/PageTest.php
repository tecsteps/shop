<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\QueryException;

it('belongs to a store', function () {
    $page = Page::factory()->create();

    expect($page->store)->toBeInstanceOf(Store::class);
});

it('casts status to PageStatus enum', function () {
    $page = Page::factory()->published()->create();

    expect($page->status)->toBeInstanceOf(PageStatus::class);
    expect($page->status)->toBe(PageStatus::Published);
});

it('enforces unique handle per store', function () {
    $store = Store::factory()->create();
    Page::factory()->create(['store_id' => $store->id, 'handle' => 'about-us']);

    Page::factory()->create(['store_id' => $store->id, 'handle' => 'about-us']);
})->throws(QueryException::class);

it('allows same handle for different stores', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Page::factory()->create(['store_id' => $storeA->id, 'handle' => 'about-us']);
    $pageB = Page::factory()->create(['store_id' => $storeB->id, 'handle' => 'about-us']);

    expect($pageB->exists)->toBeTrue();
});

it('factory creates valid page', function () {
    $page = Page::factory()->create();

    expect($page->title)->not->toBeEmpty();
    expect($page->handle)->not->toBeEmpty();
    expect($page->status)->toBe(PageStatus::Draft);
});

it('cascades delete to pages when store is deleted', function () {
    $store = Store::factory()->create();
    Page::factory()->count(2)->create(['store_id' => $store->id]);

    $store->delete();

    expect(Page::where('store_id', $store->id)->count())->toBe(0);
});
