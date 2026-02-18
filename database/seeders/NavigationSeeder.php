<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->first();

        if (! $store) {
            return;
        }

        $mainMenu = NavigationMenu::factory()->create([
            'store_id' => $store->id,
            'handle' => 'main-menu',
            'title' => 'Main Menu',
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Home',
            'url' => '/',
            'position' => 0,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Collections',
            'url' => '/collections',
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'About',
            'url' => '/pages/about-us',
            'position' => 2,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Contact',
            'url' => '/pages/contact',
            'position' => 3,
        ]);

        $footerMenu = NavigationMenu::factory()->create([
            'store_id' => $store->id,
            'handle' => 'footer-menu',
            'title' => 'Footer Menu',
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'About Us',
            'url' => '/pages/about-us',
            'position' => 0,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Shipping Policy',
            'url' => '/pages/shipping-policy',
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Contact',
            'url' => '/pages/contact',
            'position' => 2,
        ]);
    }
}
