<?php

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedPolishStore(string $hostname = 'shop.test', StoreStatus $status = StoreStatus::Active): Store
{
    $store = Store::factory()->create(['name' => 'Shop', 'status' => $status->value]);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => $hostname,
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    return $store;
}

it('renders a 404 page for unknown storefront URLs', function () {
    seedPolishStore();

    $response = $this->get('http://shop.test/pages/definitely-missing');

    $response->assertNotFound();
    $response->assertSee('Page not found');
    $response->assertSee('Return home');
});

it('renders a 503 page when the store is suspended', function () {
    seedPolishStore('paused.test', StoreStatus::Suspended);

    $response = $this->get('http://paused.test/');

    $response->assertStatus(503);
    $response->assertSee('Temporarily unavailable');
});

it('renders a 404 with a minimal fallback layout when the hostname is unknown', function () {
    $response = $this->get('http://unknown-host.test/');

    $response->assertNotFound();
    $response->assertSee('Return home');
});
