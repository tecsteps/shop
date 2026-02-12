<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;

it('scopes store-owned models to the current store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    setCurrentStore($storeA);

    Product::factory()->for($storeA)->create();
    Product::factory()->for($storeB)->create();

    expect(Product::query()->count())->toBe(1)
        ->and(Product::query()->first()?->store_id)->toBe($storeA->id)
        ->and(Product::withoutGlobalScopes()->count())->toBe(2);
});

it('auto-fills store_id when creating scoped models with current store', function () {
    $store = Store::factory()->create();

    setCurrentStore($store);

    $page = Page::query()->create([
        'title' => 'About us',
        'handle' => 'about-us',
        'body_html' => '<p>About</p>',
        'status' => PageStatus::Published->value,
        'published_at' => now(),
    ]);

    expect($page->store_id)->toBe($store->id);
});
