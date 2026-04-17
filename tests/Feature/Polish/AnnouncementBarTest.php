<?php

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('shows the configured announcement from StoreSettings', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);
    StoreSettings::query()->create([
        'store_id' => $store->getKey(),
        'settings_json' => [
            'announcement' => [
                'enabled' => true,
                'text' => 'Sitewide 20% off',
                'link' => null,
            ],
        ],
    ]);

    $response = $this->get('http://shop.test/');

    $response->assertOk();
    $response->assertSee('Sitewide 20% off');
    $response->assertSee('aria-label="Announcement"', false);
});

it('falls back to the default announcement when none is configured', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    $response = $this->get('http://shop.test/');

    $response->assertOk();
    $response->assertSee('Free shipping on orders over $50');
});

it('hides the announcement bar when explicitly disabled', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);
    StoreSettings::query()->create([
        'store_id' => $store->getKey(),
        'settings_json' => [
            'announcement' => [
                'enabled' => false,
                'text' => 'Should not appear',
            ],
        ],
    ]);

    $response = $this->get('http://shop.test/');

    $response->assertOk();
    $response->assertDontSee('Should not appear');
});
