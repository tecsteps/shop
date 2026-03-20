<?php

namespace Database\Seeders;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        $mainMenu = NavigationMenu::factory()->create([
            'store_id' => $store->id,
            'handle' => 'main-menu',
            'title' => 'Main Menu',
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => 'link',
            'label' => 'Home',
            'url' => '/',
            'position' => 0,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => 'link',
            'label' => 'Collections',
            'url' => '/collections',
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => 'page',
            'label' => 'About',
            'url' => null,
            'resource_id' => 1,
            'position' => 2,
        ]);

        $footerMenu = NavigationMenu::factory()->create([
            'store_id' => $store->id,
            'handle' => 'footer-menu',
            'title' => 'Footer Menu',
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => 'page',
            'label' => 'About Us',
            'url' => null,
            'resource_id' => 1,
            'position' => 0,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => 'page',
            'label' => 'Contact',
            'url' => null,
            'resource_id' => 2,
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => 'link',
            'label' => 'Privacy Policy',
            'url' => '/pages/privacy-policy',
            'position' => 2,
        ]);
    }
}
