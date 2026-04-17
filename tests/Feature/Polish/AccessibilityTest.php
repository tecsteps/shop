<?php

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedAccessibilityStore(): Store
{
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    return $store;
}

it('includes a skip-to-content link, landmarks, and aria labels', function () {
    seedAccessibilityStore();

    $response = $this->get('http://shop.test/');

    $response->assertOk();
    $response->assertSee('Skip to main content');
    $response->assertSee('id="main-content"', false);
    $response->assertSee('role="main"', false);
    $response->assertSee('role="contentinfo"', false);
    $response->assertSee('aria-label="Primary"', false);
    $response->assertSee('aria-label="Footer"', false);
    $response->assertSee('aria-haspopup="dialog"', false);
});

it('marks the home link as current page on the homepage', function () {
    seedAccessibilityStore();

    $response = $this->get('http://shop.test/');

    $response->assertOk();
    $response->assertSee('aria-current="page"', false);
});
