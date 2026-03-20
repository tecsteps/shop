<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFashionMenus();
        $this->seedElectronicsMenus();
    }

    private function seedFashionMenus(): void
    {
        $store = Store::where('handle', 'like', '%fashion%')->first();

        if (! $store) {
            $store = Store::first();
        }

        if (! $store) {
            return;
        }

        // Main menu
        $mainMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'handle' => 'main-menu',
            'title' => 'Main Menu',
        ]);

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Home',
            'url' => '/',
            'position' => 0,
        ]);

        // Collection items - these reference collections that will be created in Phase 2
        // For now use link type with paths
        $collectionLinks = [
            ['label' => 'New Arrivals', 'url' => '/collections/new-arrivals', 'position' => 1],
            ['label' => 'T-Shirts', 'url' => '/collections/t-shirts', 'position' => 2],
            ['label' => 'Pants & Jeans', 'url' => '/collections/pants-jeans', 'position' => 3],
            ['label' => 'Sale', 'url' => '/collections/sale', 'position' => 4],
        ];

        foreach ($collectionLinks as $link) {
            NavigationItem::create([
                'menu_id' => $mainMenu->id,
                'type' => NavigationItemType::Link,
                'label' => $link['label'],
                'url' => $link['url'],
                'position' => $link['position'],
            ]);
        }

        // Footer menu
        $footerMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'handle' => 'footer-menu',
            'title' => 'Footer Menu',
        ]);

        $pages = Page::where('store_id', $store->id)->get();
        $pageMap = $pages->keyBy('handle');

        $footerLinks = [
            ['label' => 'About Us', 'handle' => 'about', 'position' => 0],
            ['label' => 'FAQ', 'handle' => 'faq', 'position' => 1],
            ['label' => 'Shipping & Returns', 'handle' => 'shipping-returns', 'position' => 2],
            ['label' => 'Privacy Policy', 'handle' => 'privacy-policy', 'position' => 3],
            ['label' => 'Terms of Service', 'handle' => 'terms', 'position' => 4],
        ];

        foreach ($footerLinks as $link) {
            $page = $pageMap->get($link['handle']);

            if ($page) {
                NavigationItem::create([
                    'menu_id' => $footerMenu->id,
                    'type' => NavigationItemType::Page,
                    'label' => $link['label'],
                    'resource_id' => $page->id,
                    'position' => $link['position'],
                ]);
            }
        }
    }

    private function seedElectronicsMenus(): void
    {
        $store = Store::where('handle', 'like', '%electronics%')->first();

        if (! $store) {
            return;
        }

        $mainMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'handle' => 'main-menu',
            'title' => 'Main Menu',
        ]);

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Home',
            'url' => '/',
            'position' => 0,
        ]);

        $collectionLinks = [
            ['label' => 'Featured', 'url' => '/collections/featured', 'position' => 1],
            ['label' => 'Accessories', 'url' => '/collections/accessories', 'position' => 2],
        ];

        foreach ($collectionLinks as $link) {
            NavigationItem::create([
                'menu_id' => $mainMenu->id,
                'type' => NavigationItemType::Link,
                'label' => $link['label'],
                'url' => $link['url'],
                'position' => $link['position'],
            ]);
        }
    }
}
