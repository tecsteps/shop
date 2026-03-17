<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        $this->seedFashionNavigation($fashion);
        $this->seedElectronicsNavigation($electronics);
    }

    protected function seedFashionNavigation(Store $store): void
    {
        $collections = Collection::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $pages = Page::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        // Main Menu
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

        NavigationItem::factory()->forCollection($collections['new-arrivals'])->create([
            'menu_id' => $mainMenu->id,
            'label' => 'New Arrivals',
            'position' => 1,
        ]);

        NavigationItem::factory()->forCollection($collections['t-shirts'])->create([
            'menu_id' => $mainMenu->id,
            'label' => 'T-Shirts',
            'position' => 2,
        ]);

        NavigationItem::factory()->forCollection($collections['pants-jeans'])->create([
            'menu_id' => $mainMenu->id,
            'label' => 'Pants & Jeans',
            'position' => 3,
        ]);

        NavigationItem::factory()->forCollection($collections['sale'])->create([
            'menu_id' => $mainMenu->id,
            'label' => 'Sale',
            'position' => 4,
        ]);

        // Footer Menu
        $footerMenu = NavigationMenu::factory()->create([
            'store_id' => $store->id,
            'handle' => 'footer-menu',
            'title' => 'Footer Menu',
        ]);

        NavigationItem::factory()->forPage($pages['about'])->create([
            'menu_id' => $footerMenu->id,
            'label' => 'About Us',
            'position' => 0,
        ]);

        NavigationItem::factory()->forPage($pages['faq'])->create([
            'menu_id' => $footerMenu->id,
            'label' => 'FAQ',
            'position' => 1,
        ]);

        NavigationItem::factory()->forPage($pages['shipping-returns'])->create([
            'menu_id' => $footerMenu->id,
            'label' => 'Shipping & Returns',
            'position' => 2,
        ]);

        NavigationItem::factory()->forPage($pages['privacy-policy'])->create([
            'menu_id' => $footerMenu->id,
            'label' => 'Privacy Policy',
            'position' => 3,
        ]);

        NavigationItem::factory()->forPage($pages['terms'])->create([
            'menu_id' => $footerMenu->id,
            'label' => 'Terms of Service',
            'position' => 4,
        ]);
    }

    protected function seedElectronicsNavigation(Store $store): void
    {
        $collections = Collection::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

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

        NavigationItem::factory()->forCollection($collections['featured'])->create([
            'menu_id' => $mainMenu->id,
            'label' => 'Featured',
            'position' => 1,
        ]);

        NavigationItem::factory()->forCollection($collections['accessories'])->create([
            'menu_id' => $mainMenu->id,
            'label' => 'Accessories',
            'position' => 2,
        ]);
    }
}
