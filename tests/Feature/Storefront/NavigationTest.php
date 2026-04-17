<?php

use App\Enums\NavigationItemType;
use App\Enums\StoreDomainType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedStorefrontStoreForNav(): Store
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

it('renders menu items from the main-menu handle', function () {
    $store = seedStorefrontStoreForNav();

    $menu = NavigationMenu::query()->create([
        'store_id' => $store->getKey(),
        'handle' => 'main-menu',
        'title' => 'Main menu',
    ]);

    NavigationItem::query()->create([
        'menu_id' => $menu->getKey(),
        'type' => NavigationItemType::Link->value,
        'label' => 'Home',
        'url' => '/',
        'position' => 0,
    ]);

    NavigationItem::query()->create([
        'menu_id' => $menu->getKey(),
        'type' => NavigationItemType::Link->value,
        'label' => 'Catalog',
        'url' => '/collections/all',
        'position' => 1,
    ]);

    $response = $this->get('http://shop.test/');

    $response->assertOk();
    $response->assertSee('Home');
    $response->assertSee('Catalog');
});
