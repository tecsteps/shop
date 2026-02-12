<?php

namespace Database\Seeders;

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
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        $this->seedFashionNavigation($fashion);
        $this->seedElectronicsNavigation($electronics);
    }

    private function seedFashionNavigation(Store $store): void
    {
        // Look up collections
        $newArrivals = Collection::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'new-arrivals')->firstOrFail();
        $tShirts = Collection::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 't-shirts')->firstOrFail();
        $pantsJeans = Collection::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'pants-jeans')->firstOrFail();
        $sale = Collection::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'sale')->firstOrFail();

        // Look up pages
        $about = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'about')->firstOrFail();
        $faq = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'faq')->firstOrFail();
        $shippingReturns = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'shipping-returns')->firstOrFail();
        $privacy = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'privacy-policy')->firstOrFail();
        $terms = Page::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'terms')->firstOrFail();

        // Main Menu
        $mainMenu = NavigationMenu::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['title' => 'Main Menu'],
        );

        $mainItems = [
            ['label' => 'Home', 'type' => 'link', 'url' => '/', 'resource_id' => null, 'position' => 0],
            ['label' => 'New Arrivals', 'type' => 'collection', 'url' => null, 'resource_id' => $newArrivals->id, 'position' => 1],
            ['label' => 'T-Shirts', 'type' => 'collection', 'url' => null, 'resource_id' => $tShirts->id, 'position' => 2],
            ['label' => 'Pants & Jeans', 'type' => 'collection', 'url' => null, 'resource_id' => $pantsJeans->id, 'position' => 3],
            ['label' => 'Sale', 'type' => 'collection', 'url' => null, 'resource_id' => $sale->id, 'position' => 4],
        ];

        foreach ($mainItems as $item) {
            NavigationItem::firstOrCreate(
                ['menu_id' => $mainMenu->id, 'label' => $item['label']],
                $item,
            );
        }

        // Footer Menu
        $footerMenu = NavigationMenu::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'footer-menu'],
            ['title' => 'Footer Menu'],
        );

        $footerItems = [
            ['label' => 'About Us', 'type' => 'page', 'url' => null, 'resource_id' => $about->id, 'position' => 0],
            ['label' => 'FAQ', 'type' => 'page', 'url' => null, 'resource_id' => $faq->id, 'position' => 1],
            ['label' => 'Shipping & Returns', 'type' => 'page', 'url' => null, 'resource_id' => $shippingReturns->id, 'position' => 2],
            ['label' => 'Privacy Policy', 'type' => 'page', 'url' => null, 'resource_id' => $privacy->id, 'position' => 3],
            ['label' => 'Terms of Service', 'type' => 'page', 'url' => null, 'resource_id' => $terms->id, 'position' => 4],
        ];

        foreach ($footerItems as $item) {
            NavigationItem::firstOrCreate(
                ['menu_id' => $footerMenu->id, 'label' => $item['label']],
                $item,
            );
        }
    }

    private function seedElectronicsNavigation(Store $store): void
    {
        $featured = Collection::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'featured')->firstOrFail();
        $accessories = Collection::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'accessories')->firstOrFail();

        // Main Menu
        $mainMenu = NavigationMenu::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['title' => 'Main Menu'],
        );

        $mainItems = [
            ['label' => 'Home', 'type' => 'link', 'url' => '/', 'resource_id' => null, 'position' => 0],
            ['label' => 'Featured', 'type' => 'collection', 'url' => null, 'resource_id' => $featured->id, 'position' => 1],
            ['label' => 'Accessories', 'type' => 'collection', 'url' => null, 'resource_id' => $accessories->id, 'position' => 2],
        ];

        foreach ($mainItems as $item) {
            NavigationItem::firstOrCreate(
                ['menu_id' => $mainMenu->id, 'label' => $item['label']],
                $item,
            );
        }
    }
}
