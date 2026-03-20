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
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        $this->seedFashionNavigation($fashion);
        $this->seedElectronicsNavigation($electronics);
    }

    private function seedFashionNavigation(Store $store): void
    {
        app()->instance('current_store', $store);

        // Load collections and pages by handle
        $newArrivals = Collection::where('store_id', $store->id)->where('handle', 'new-arrivals')->first();
        $tShirts = Collection::where('store_id', $store->id)->where('handle', 't-shirts')->first();
        $pantsJeans = Collection::where('store_id', $store->id)->where('handle', 'pants-jeans')->first();
        $sale = Collection::where('store_id', $store->id)->where('handle', 'sale')->first();

        $about = Page::where('store_id', $store->id)->where('handle', 'about')->first();
        $faq = Page::where('store_id', $store->id)->where('handle', 'faq')->first();
        $shippingReturns = Page::where('store_id', $store->id)->where('handle', 'shipping-returns')->first();
        $privacy = Page::where('store_id', $store->id)->where('handle', 'privacy-policy')->first();
        $terms = Page::where('store_id', $store->id)->where('handle', 'terms')->first();

        // Main Menu
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
            'type' => 'collection',
            'label' => 'New Arrivals',
            'url' => null,
            'resource_id' => $newArrivals->id,
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => 'collection',
            'label' => 'T-Shirts',
            'url' => null,
            'resource_id' => $tShirts->id,
            'position' => 2,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => 'collection',
            'label' => 'Pants & Jeans',
            'url' => null,
            'resource_id' => $pantsJeans->id,
            'position' => 3,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => 'collection',
            'label' => 'Sale',
            'url' => null,
            'resource_id' => $sale->id,
            'position' => 4,
        ]);

        // Footer Menu
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
            'resource_id' => $about->id,
            'position' => 0,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => 'page',
            'label' => 'FAQ',
            'url' => null,
            'resource_id' => $faq->id,
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => 'page',
            'label' => 'Shipping & Returns',
            'url' => null,
            'resource_id' => $shippingReturns->id,
            'position' => 2,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => 'page',
            'label' => 'Privacy Policy',
            'url' => null,
            'resource_id' => $privacy->id,
            'position' => 3,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => 'page',
            'label' => 'Terms of Service',
            'url' => null,
            'resource_id' => $terms->id,
            'position' => 4,
        ]);
    }

    private function seedElectronicsNavigation(Store $store): void
    {
        app()->instance('current_store', $store);

        $featured = Collection::where('store_id', $store->id)->where('handle', 'featured')->first();
        $accessories = Collection::where('store_id', $store->id)->where('handle', 'accessories')->first();

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
            'type' => 'collection',
            'label' => 'Featured',
            'url' => null,
            'resource_id' => $featured->id,
            'position' => 1,
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => 'collection',
            'label' => 'Accessories',
            'url' => null,
            'resource_id' => $accessories->id,
            'position' => 2,
        ]);
    }
}
