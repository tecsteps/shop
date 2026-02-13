<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationSeeder extends Seeder
{
    /**
     * Seed navigation menus and items for both stores.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedAcmeFashion();
            $this->seedAcmeElectronics();
        });
    }

    private function seedAcmeFashion(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $collections = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $pages = Page::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $mainMenu = NavigationMenu::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['title' => 'Main Menu'],
        );

        if ($mainMenu->items()->count() === 0) {
            $mainMenuItems = [
                ['label' => 'Home', 'type' => 'link', 'url' => '/', 'position' => 0],
                ['label' => 'New Arrivals', 'type' => 'collection', 'resource_id' => $collections['new-arrivals'] ?? null, 'position' => 1],
                ['label' => 'T-Shirts', 'type' => 'collection', 'resource_id' => $collections['t-shirts'] ?? null, 'position' => 2],
                ['label' => 'Pants & Jeans', 'type' => 'collection', 'resource_id' => $collections['pants-jeans'] ?? null, 'position' => 3],
                ['label' => 'Sale', 'type' => 'collection', 'resource_id' => $collections['sale'] ?? null, 'position' => 4],
            ];

            foreach ($mainMenuItems as $item) {
                NavigationItem::create([
                    'menu_id' => $mainMenu->id,
                    'type' => $item['type'],
                    'label' => $item['label'],
                    'url' => $item['url'] ?? null,
                    'resource_id' => $item['resource_id'] ?? null,
                    'position' => $item['position'],
                ]);
            }
        }

        $footerMenu = NavigationMenu::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'footer-menu'],
            ['title' => 'Footer Menu'],
        );

        if ($footerMenu->items()->count() === 0) {
            $footerItems = [
                ['label' => 'About Us', 'type' => 'page', 'resource_id' => $pages['about'] ?? null, 'position' => 0],
                ['label' => 'FAQ', 'type' => 'page', 'resource_id' => $pages['faq'] ?? null, 'position' => 1],
                ['label' => 'Shipping & Returns', 'type' => 'page', 'resource_id' => $pages['shipping-returns'] ?? null, 'position' => 2],
                ['label' => 'Privacy Policy', 'type' => 'page', 'resource_id' => $pages['privacy-policy'] ?? null, 'position' => 3],
                ['label' => 'Terms of Service', 'type' => 'page', 'resource_id' => $pages['terms'] ?? null, 'position' => 4],
            ];

            foreach ($footerItems as $item) {
                NavigationItem::create([
                    'menu_id' => $footerMenu->id,
                    'type' => $item['type'],
                    'label' => $item['label'],
                    'url' => null,
                    'resource_id' => $item['resource_id'] ?? null,
                    'position' => $item['position'],
                ]);
            }
        }
    }

    private function seedAcmeElectronics(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();

        $collections = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $mainMenu = NavigationMenu::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['title' => 'Main Menu'],
        );

        if ($mainMenu->items()->count() === 0) {
            $items = [
                ['label' => 'Home', 'type' => 'link', 'url' => '/', 'position' => 0],
                ['label' => 'Featured', 'type' => 'collection', 'resource_id' => $collections['featured'] ?? null, 'position' => 1],
                ['label' => 'Accessories', 'type' => 'collection', 'resource_id' => $collections['accessories'] ?? null, 'position' => 2],
            ];

            foreach ($items as $item) {
                NavigationItem::create([
                    'menu_id' => $mainMenu->id,
                    'type' => $item['type'],
                    'label' => $item['label'],
                    'url' => $item['url'] ?? null,
                    'resource_id' => $item['resource_id'] ?? null,
                    'position' => $item['position'],
                ]);
            }
        }
    }
}
