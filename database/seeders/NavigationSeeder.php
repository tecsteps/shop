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
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $this->seedMainMenu($store);
        $this->seedFooterMenu($store);
    }

    private function seedMainMenu(Store $store): void
    {
        $menu = NavigationMenu::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'name' => 'Main Menu',
            'handle' => 'main',
        ]);

        $tShirts = Collection::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 't-shirts')->first();
        $newArrivals = Collection::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'new-arrivals')->first();
        $sale = Collection::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'sale')->first();
        $aboutPage = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'about')->first();

        $items = [
            ['title' => 'Home', 'type' => NavigationItemType::Link, 'url' => '/', 'position' => 0],
            ['title' => 'T-Shirts', 'type' => NavigationItemType::Collection, 'resource_id' => $tShirts?->id, 'position' => 1],
            ['title' => 'New Arrivals', 'type' => NavigationItemType::Collection, 'resource_id' => $newArrivals?->id, 'position' => 2],
            ['title' => 'Sale', 'type' => NavigationItemType::Collection, 'resource_id' => $sale?->id, 'position' => 3],
            ['title' => 'About', 'type' => NavigationItemType::Page, 'resource_id' => $aboutPage?->id, 'position' => 4],
        ];

        foreach ($items as $item) {
            NavigationItem::create([
                'menu_id' => $menu->id,
                ...$item,
            ]);
        }
    }

    private function seedFooterMenu(Store $store): void
    {
        $menu = NavigationMenu::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'name' => 'Footer Menu',
            'handle' => 'footer',
        ]);

        $aboutPage = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'about')->first();
        $contactPage = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'contact')->first();
        $faqPage = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'faq')->first();
        $termsPage = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'terms')->first();

        $items = [
            ['title' => 'About', 'type' => NavigationItemType::Page, 'resource_id' => $aboutPage?->id, 'position' => 0],
            ['title' => 'Contact', 'type' => NavigationItemType::Page, 'resource_id' => $contactPage?->id, 'position' => 1],
            ['title' => 'FAQ', 'type' => NavigationItemType::Page, 'resource_id' => $faqPage?->id, 'position' => 2],
            ['title' => 'Terms', 'type' => NavigationItemType::Page, 'resource_id' => $termsPage?->id, 'position' => 3],
        ];

        foreach ($items as $item) {
            NavigationItem::create([
                'menu_id' => $menu->id,
                ...$item,
            ]);
        }
    }
}
