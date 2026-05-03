<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $collection = Collection::query()
            ->where('store_id', $store->id)
            ->where('handle', 'summer-essentials')
            ->firstOrFail();
        $page = Page::query()
            ->where('store_id', $store->id)
            ->where('handle', 'about')
            ->firstOrFail();

        $mainMenu = NavigationMenu::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'main-menu',
            ],
            ['title' => 'Main menu'],
        );

        $footerMenu = NavigationMenu::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'footer-menu',
            ],
            ['title' => 'Footer menu'],
        );

        $mainMenu->items()->updateOrCreate(
            ['position' => 0],
            [
                'type' => NavigationItemType::Link,
                'label' => 'Home',
                'url' => '/',
                'resource_id' => null,
            ],
        );

        $mainMenu->items()->updateOrCreate(
            ['position' => 1],
            [
                'type' => NavigationItemType::Collection,
                'label' => 'Summer Essentials',
                'url' => null,
                'resource_id' => $collection->id,
            ],
        );

        $mainMenu->items()->updateOrCreate(
            ['position' => 2],
            [
                'type' => NavigationItemType::Page,
                'label' => 'About',
                'url' => null,
                'resource_id' => $page->id,
            ],
        );

        foreach ([
            ['label' => 'Search', 'url' => '/search'],
            ['label' => 'Account', 'url' => '/account/login'],
        ] as $position => $item) {
            $footerMenu->items()->updateOrCreate(
                ['position' => $position],
                [
                    'type' => NavigationItemType::Link,
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'resource_id' => null,
                ],
            );
        }
    }
}
