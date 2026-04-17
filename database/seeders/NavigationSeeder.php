<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

            $this->seedFashionNavigation($fashion);
            $this->seedElectronicsNavigation($electronics);
        });
    }

    private function seedFashionNavigation(Store $store): void
    {
        $collections = Collection::where('store_id', $store->id)->get()->keyBy('handle');
        $pages = Page::where('store_id', $store->id)->get()->keyBy('handle');

        // Main Menu
        $mainMenu = NavigationMenu::firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['title' => 'Main Menu'],
        );

        $mainItems = [
            ['label' => 'Home', 'type' => NavigationItemType::Link, 'url' => '/', 'position' => 0],
            ['label' => 'New Arrivals', 'type' => NavigationItemType::Collection, 'resource_id' => $collections->get('new-arrivals')?->id, 'position' => 1],
            ['label' => 'T-Shirts', 'type' => NavigationItemType::Collection, 'resource_id' => $collections->get('t-shirts')?->id, 'position' => 2],
            ['label' => 'Pants & Jeans', 'type' => NavigationItemType::Collection, 'resource_id' => $collections->get('pants-jeans')?->id, 'position' => 3],
            ['label' => 'Sale', 'type' => NavigationItemType::Collection, 'resource_id' => $collections->get('sale')?->id, 'position' => 4],
        ];

        foreach ($mainItems as $item) {
            NavigationItem::firstOrCreate(
                ['menu_id' => $mainMenu->id, 'label' => $item['label']],
                [
                    'type' => $item['type'],
                    'url' => $item['url'] ?? null,
                    'resource_id' => $item['resource_id'] ?? null,
                    'position' => $item['position'],
                ],
            );
        }

        // Footer Menu
        $footerMenu = NavigationMenu::firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'footer-menu'],
            ['title' => 'Footer Menu'],
        );

        $footerItems = [
            ['label' => 'About Us', 'type' => NavigationItemType::Page, 'resource_id' => $pages->get('about')?->id, 'position' => 0],
            ['label' => 'FAQ', 'type' => NavigationItemType::Page, 'resource_id' => $pages->get('faq')?->id, 'position' => 1],
            ['label' => 'Shipping & Returns', 'type' => NavigationItemType::Page, 'resource_id' => $pages->get('shipping-returns')?->id, 'position' => 2],
            ['label' => 'Privacy Policy', 'type' => NavigationItemType::Page, 'resource_id' => $pages->get('privacy-policy')?->id, 'position' => 3],
            ['label' => 'Terms of Service', 'type' => NavigationItemType::Page, 'resource_id' => $pages->get('terms')?->id, 'position' => 4],
        ];

        foreach ($footerItems as $item) {
            NavigationItem::firstOrCreate(
                ['menu_id' => $footerMenu->id, 'label' => $item['label']],
                [
                    'type' => $item['type'],
                    'url' => null,
                    'resource_id' => $item['resource_id'],
                    'position' => $item['position'],
                ],
            );
        }
    }

    private function seedElectronicsNavigation(Store $store): void
    {
        $collections = Collection::where('store_id', $store->id)->get()->keyBy('handle');

        $mainMenu = NavigationMenu::firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['title' => 'Main Menu'],
        );

        $items = [
            ['label' => 'Home', 'type' => NavigationItemType::Link, 'url' => '/', 'position' => 0],
            ['label' => 'Featured', 'type' => NavigationItemType::Collection, 'resource_id' => $collections->get('featured')?->id, 'position' => 1],
            ['label' => 'Accessories', 'type' => NavigationItemType::Collection, 'resource_id' => $collections->get('accessories')?->id, 'position' => 2],
        ];

        foreach ($items as $item) {
            NavigationItem::firstOrCreate(
                ['menu_id' => $mainMenu->id, 'label' => $item['label']],
                [
                    'type' => $item['type'],
                    'url' => $item['url'] ?? null,
                    'resource_id' => $item['resource_id'] ?? null,
                    'position' => $item['position'],
                ],
            );
        }
    }
}
