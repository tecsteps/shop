<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationMenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFashion();
        $this->seedElectronics();
    }

    private function seedFashion(): void
    {
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

        $mainMenu = NavigationMenu::firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['name' => 'Main Menu']
        );

        if (! $mainMenu->items()->exists()) {
            $newArrivals = Collection::where('store_id', $store->id)->where('handle', 'new-arrivals')->first();
            $tshirts = Collection::where('store_id', $store->id)->where('handle', 't-shirts')->first();
            $pants = Collection::where('store_id', $store->id)->where('handle', 'pants-jeans')->first();
            $sale = Collection::where('store_id', $store->id)->where('handle', 'sale')->first();

            NavigationItem::create(['menu_id' => $mainMenu->id, 'title' => 'Home', 'type' => NavigationItemType::Link, 'url' => '/', 'position' => 0]);
            NavigationItem::create(['menu_id' => $mainMenu->id, 'title' => 'New Arrivals', 'type' => NavigationItemType::Collection, 'resource_id' => $newArrivals?->id, 'url' => '/collections/new-arrivals', 'position' => 1]);
            NavigationItem::create(['menu_id' => $mainMenu->id, 'title' => 'T-Shirts', 'type' => NavigationItemType::Collection, 'resource_id' => $tshirts?->id, 'url' => '/collections/t-shirts', 'position' => 2]);
            NavigationItem::create(['menu_id' => $mainMenu->id, 'title' => 'Pants & Jeans', 'type' => NavigationItemType::Collection, 'resource_id' => $pants?->id, 'url' => '/collections/pants-jeans', 'position' => 3]);
            NavigationItem::create(['menu_id' => $mainMenu->id, 'title' => 'Sale', 'type' => NavigationItemType::Collection, 'resource_id' => $sale?->id, 'url' => '/collections/sale', 'position' => 4]);
        }

        $footerMenu = NavigationMenu::firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'footer-menu'],
            ['name' => 'Footer Menu']
        );

        if (! $footerMenu->items()->exists()) {
            $about = Page::where('store_id', $store->id)->where('handle', 'about')->first();
            $faq = Page::where('store_id', $store->id)->where('handle', 'faq')->first();
            $shipping = Page::where('store_id', $store->id)->where('handle', 'shipping-returns')->first();
            $privacy = Page::where('store_id', $store->id)->where('handle', 'privacy-policy')->first();
            $terms = Page::where('store_id', $store->id)->where('handle', 'terms')->first();

            NavigationItem::create(['menu_id' => $footerMenu->id, 'title' => 'About Us', 'type' => NavigationItemType::Page, 'resource_id' => $about?->id, 'url' => '/pages/about', 'position' => 0]);
            NavigationItem::create(['menu_id' => $footerMenu->id, 'title' => 'FAQ', 'type' => NavigationItemType::Page, 'resource_id' => $faq?->id, 'url' => '/pages/faq', 'position' => 1]);
            NavigationItem::create(['menu_id' => $footerMenu->id, 'title' => 'Shipping & Returns', 'type' => NavigationItemType::Page, 'resource_id' => $shipping?->id, 'url' => '/pages/shipping-returns', 'position' => 2]);
            NavigationItem::create(['menu_id' => $footerMenu->id, 'title' => 'Privacy Policy', 'type' => NavigationItemType::Page, 'resource_id' => $privacy?->id, 'url' => '/pages/privacy-policy', 'position' => 3]);
            NavigationItem::create(['menu_id' => $footerMenu->id, 'title' => 'Terms of Service', 'type' => NavigationItemType::Page, 'resource_id' => $terms?->id, 'url' => '/pages/terms', 'position' => 4]);
        }
    }

    private function seedElectronics(): void
    {
        $store = Store::where('slug', 'acme-electronics')->firstOrFail();

        $mainMenu = NavigationMenu::firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['name' => 'Main Menu']
        );

        if (! $mainMenu->items()->exists()) {
            $featured = Collection::where('store_id', $store->id)->where('handle', 'featured')->first();
            $accessories = Collection::where('store_id', $store->id)->where('handle', 'accessories')->first();

            NavigationItem::create(['menu_id' => $mainMenu->id, 'title' => 'Home', 'type' => NavigationItemType::Link, 'url' => '/', 'position' => 0]);
            NavigationItem::create(['menu_id' => $mainMenu->id, 'title' => 'Featured', 'type' => NavigationItemType::Collection, 'resource_id' => $featured?->id, 'url' => '/collections/featured', 'position' => 1]);
            NavigationItem::create(['menu_id' => $mainMenu->id, 'title' => 'Accessories', 'type' => NavigationItemType::Collection, 'resource_id' => $accessories?->id, 'url' => '/collections/accessories', 'position' => 2]);
        }
    }
}
