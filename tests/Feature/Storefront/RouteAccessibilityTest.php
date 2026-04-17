<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Theme;
use App\Models\ThemeSettings;

beforeEach(function () {
    $this->store = Store::factory()->create(['name' => 'Test Store']);
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'test-store.test',
        'type' => 'storefront',
    ]);

    $theme = Theme::factory()->published()->create(['store_id' => $this->store->id]);
    ThemeSettings::factory()->create(['theme_id' => $theme->id]);
});

it('home page returns 200', function () {
    $response = $this->get('https://test-store.test/');
    $response->assertOk();
});

it('collections index returns 200', function () {
    $response = $this->get('https://test-store.test/collections');
    $response->assertOk();
});

it('collection show returns 200 for valid collection', function () {
    Collection::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'summer',
        'status' => CollectionStatus::Active,
    ]);

    $response = $this->get('https://test-store.test/collections/summer');
    $response->assertOk();
});

it('collection show returns 404 for missing collection', function () {
    $response = $this->get('https://test-store.test/collections/nonexistent');
    $response->assertNotFound();
});

it('product show returns 200 for valid product', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 't-shirt',
        'status' => 'active',
    ]);

    $response = $this->get('https://test-store.test/products/t-shirt');
    $response->assertOk();
});

it('product show returns 404 for missing product', function () {
    $response = $this->get('https://test-store.test/products/nonexistent');
    $response->assertNotFound();
});

it('cart page returns 200', function () {
    $response = $this->get('https://test-store.test/cart');
    $response->assertOk();
});

it('search page returns 200', function () {
    $response = $this->get('https://test-store.test/search?q=test');
    $response->assertOk();
});

it('pages show returns 200 for published page', function () {
    Page::factory()->published()->create([
        'store_id' => $this->store->id,
        'handle' => 'about',
    ]);

    $response = $this->get('https://test-store.test/pages/about');
    $response->assertOk();
});

it('pages show returns 404 for draft page', function () {
    Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'draft-page',
        'status' => 'draft',
    ]);

    $response = $this->get('https://test-store.test/pages/draft-page');
    $response->assertNotFound();
});

it('pages show returns 404 for missing page', function () {
    $response = $this->get('https://test-store.test/pages/nonexistent');
    $response->assertNotFound();
});
