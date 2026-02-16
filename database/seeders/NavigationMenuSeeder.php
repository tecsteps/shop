<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationMenuSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

        $mainMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'name' => 'Main Menu',
            'handle' => 'main-menu',
        ]);

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'title' => 'Collections',
            'type' => NavigationItemType::Link,
            'url' => '/collections',
            'position' => 0,
        ]);

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'title' => 'About',
            'type' => NavigationItemType::Link,
            'url' => '/pages/about',
            'position' => 1,
        ]);

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'title' => 'Contact',
            'type' => NavigationItemType::Link,
            'url' => '/pages/contact',
            'position' => 2,
        ]);

        $footerMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'name' => 'Footer Menu',
            'handle' => 'footer-menu',
        ]);

        NavigationItem::create([
            'menu_id' => $footerMenu->id,
            'title' => 'About Us',
            'type' => NavigationItemType::Link,
            'url' => '/pages/about',
            'position' => 0,
        ]);

        NavigationItem::create([
            'menu_id' => $footerMenu->id,
            'title' => 'Contact',
            'type' => NavigationItemType::Link,
            'url' => '/pages/contact',
            'position' => 1,
        ]);

        NavigationItem::create([
            'menu_id' => $footerMenu->id,
            'title' => 'Search',
            'type' => NavigationItemType::Link,
            'url' => '/search',
            'position' => 2,
        ]);
    }
}
