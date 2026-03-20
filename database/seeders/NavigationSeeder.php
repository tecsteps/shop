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
        $store = Store::where('handle', 'acme-fashion')->first();

        if (! $store) {
            return;
        }

        app()->instance('current_store', $store);

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

        $collectionItems = [
            ['label' => 'New Arrivals', 'handle' => 'new-arrivals', 'position' => 1],
            ['label' => 'T-Shirts', 'handle' => 't-shirts', 'position' => 2],
            ['label' => 'Pants & Jeans', 'handle' => 'pants-jeans', 'position' => 3],
            ['label' => 'Sale', 'handle' => 'sale', 'position' => 4],
        ];

        $collections = \App\Models\Collection::where('store_id', $store->id)->get()->keyBy('handle');

        foreach ($collectionItems as $item) {
            $collection = $collections->get($item['handle']);
            NavigationItem::create([
                'menu_id' => $mainMenu->id,
                'type' => $collection ? NavigationItemType::Collection : NavigationItemType::Link,
                'label' => $item['label'],
                'url' => $collection ? null : '/collections/'.$item['handle'],
                'resource_id' => $collection?->id,
                'position' => $item['position'],
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
        $store = Store::where('handle', 'acme-electronics')->first();

        if (! $store) {
            return;
        }

        app()->instance('current_store', $store);

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

        $collectionItems = [
            ['label' => 'Featured', 'handle' => 'featured', 'position' => 1],
            ['label' => 'Accessories', 'handle' => 'accessories', 'position' => 2],
        ];

        $collections = \App\Models\Collection::where('store_id', $store->id)->get()->keyBy('handle');

        foreach ($collectionItems as $item) {
            $collection = $collections->get($item['handle']);
            NavigationItem::create([
                'menu_id' => $mainMenu->id,
                'type' => $collection ? NavigationItemType::Collection : NavigationItemType::Link,
                'label' => $item['label'],
                'url' => $collection ? null : '/collections/'.$item['handle'],
                'resource_id' => $collection?->id,
                'position' => $item['position'],
            ]);
        }
    }
}
