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
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $this->seedFashionNavigation($fashion);
        $this->seedElectronicsNavigation($electronics);
    }

    private function seedFashionNavigation(Store $store): void
    {
        $collections = Collection::query()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $pages = Page::query()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        // Main menu
        $mainMenu = NavigationMenu::factory()->mainMenu()->create([
            'store_id' => $store->id,
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
            'type' => NavigationItemType::Collection,
            'label' => 'New Arrivals',
            'url' => null,
            'resource_id' => $collections['new-arrivals'],
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Collection,
            'label' => 'T-Shirts',
            'url' => null,
            'resource_id' => $collections['t-shirts'],
            'position' => 2,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Collection,
            'label' => 'Pants & Jeans',
            'url' => null,
            'resource_id' => $collections['pants-jeans'],
            'position' => 3,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Collection,
            'label' => 'Sale',
            'url' => null,
            'resource_id' => $collections['sale'],
            'position' => 4,
        ]);

        // Footer menu
        $footerMenu = NavigationMenu::factory()->footerMenu()->create([
            'store_id' => $store->id,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'About Us',
            'url' => null,
            'resource_id' => $pages['about'],
            'position' => 0,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'FAQ',
            'url' => null,
            'resource_id' => $pages['faq'],
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'Shipping & Returns',
            'url' => null,
            'resource_id' => $pages['shipping-returns'],
            'position' => 2,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'Privacy Policy',
            'url' => null,
            'resource_id' => $pages['privacy-policy'],
            'position' => 3,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'Terms of Service',
            'url' => null,
            'resource_id' => $pages['terms'],
            'position' => 4,
        ]);
    }

    private function seedElectronicsNavigation(Store $store): void
    {
        $collections = Collection::query()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $mainMenu = NavigationMenu::factory()->mainMenu()->create([
            'store_id' => $store->id,
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
            'type' => NavigationItemType::Collection,
            'label' => 'Featured',
            'url' => null,
            'resource_id' => $collections['featured'],
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Collection,
            'label' => 'Accessories',
            'url' => null,
            'resource_id' => $collections['accessories'],
            'position' => 2,
        ]);
    }
}
