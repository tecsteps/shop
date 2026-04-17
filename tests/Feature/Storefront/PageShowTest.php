<?php

use App\Enums\PageStatus;
use App\Enums\StoreDomainType;
use App\Models\Page;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedStorefrontStoreForPage(string $hostname = 'shop.test'): Store
{
    $store = Store::factory()->create(['name' => 'Shop']);

    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => $hostname,
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    return $store;
}

it('renders a published page by handle', function () {
    $store = seedStorefrontStoreForPage();

    Page::query()->create([
        'store_id' => $store->getKey(),
        'title' => 'About Us',
        'handle' => 'about',
        'body_html' => '<p>Hello visitors</p>',
        'status' => PageStatus::Published->value,
        'published_at' => now(),
    ]);

    $response = $this->get('http://shop.test/pages/about');

    $response->assertOk();
    $response->assertSee('About Us');
    $response->assertSee('Hello visitors', false);
});

it('404s for draft pages', function () {
    $store = seedStorefrontStoreForPage();

    Page::query()->create([
        'store_id' => $store->getKey(),
        'title' => 'Secret',
        'handle' => 'secret',
        'body_html' => '<p>Not yet</p>',
        'status' => PageStatus::Draft->value,
    ]);

    $response = $this->get('http://shop.test/pages/secret');

    $response->assertNotFound();
});

it('404s for unknown handles', function () {
    seedStorefrontStoreForPage();

    $response = $this->get('http://shop.test/pages/unknown-handle');

    $response->assertNotFound();
});
