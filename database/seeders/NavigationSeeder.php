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
        $this->seedAcmeFashion();
        $this->seedAcmeElectronics();
    }

    private function seedAcmeFashion(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->first();
        if (! $store) {
            return;
        }

        $newArrivals = Collection::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'new-arrivals')->first();
        $tShirts = Collection::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 't-shirts')->first();
        $pantsJeans = Collection::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'pants-jeans')->first();
        $saleCollection = Collection::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'sale')->first();

        $aboutPage = Page::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'about')->first();
        $faqPage = Page::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'faq')->first();
        $shippingPage = Page::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'shipping-returns')->first();
        $privacyPage = Page::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'privacy-policy')->first();
        $termsPage = Page::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'terms')->first();

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

        if ($newArrivals) {
            NavigationItem::factory()->create([
                'menu_id' => $mainMenu->id,
                'type' => NavigationItemType::Collection,
                'label' => 'New Arrivals',
                'url' => '/collections/new-arrivals',
                'resource_id' => $newArrivals->id,
                'position' => 1,
            ]);
        }

        if ($tShirts) {
            NavigationItem::factory()->create([
                'menu_id' => $mainMenu->id,
                'type' => NavigationItemType::Collection,
                'label' => 'T-Shirts',
                'url' => '/collections/t-shirts',
                'resource_id' => $tShirts->id,
                'position' => 2,
            ]);
        }

        if ($pantsJeans) {
            NavigationItem::factory()->create([
                'menu_id' => $mainMenu->id,
                'type' => NavigationItemType::Collection,
                'label' => 'Pants & Jeans',
                'url' => '/collections/pants-jeans',
                'resource_id' => $pantsJeans->id,
                'position' => 3,
            ]);
        }

        if ($saleCollection) {
            NavigationItem::factory()->create([
                'menu_id' => $mainMenu->id,
                'type' => NavigationItemType::Collection,
                'label' => 'Sale',
                'url' => '/collections/sale',
                'resource_id' => $saleCollection->id,
                'position' => 4,
            ]);
        }

        // Footer Menu
        $footerMenu = NavigationMenu::factory()->create([
            'store_id' => $store->id,
            'handle' => 'footer-menu',
            'title' => 'Footer Menu',
        ]);

        if ($aboutPage) {
            NavigationItem::factory()->create([
                'menu_id' => $footerMenu->id,
                'type' => NavigationItemType::Page,
                'label' => 'About Us',
                'url' => '/pages/about',
                'resource_id' => $aboutPage->id,
                'position' => 0,
            ]);
        }

        if ($faqPage) {
            NavigationItem::factory()->create([
                'menu_id' => $footerMenu->id,
                'type' => NavigationItemType::Page,
                'label' => 'FAQ',
                'url' => '/pages/faq',
                'resource_id' => $faqPage->id,
                'position' => 1,
            ]);
        }

        if ($shippingPage) {
            NavigationItem::factory()->create([
                'menu_id' => $footerMenu->id,
                'type' => NavigationItemType::Page,
                'label' => 'Shipping & Returns',
                'url' => '/pages/shipping-returns',
                'resource_id' => $shippingPage->id,
                'position' => 2,
            ]);
        }

        if ($privacyPage) {
            NavigationItem::factory()->create([
                'menu_id' => $footerMenu->id,
                'type' => NavigationItemType::Page,
                'label' => 'Privacy Policy',
                'url' => '/pages/privacy-policy',
                'resource_id' => $privacyPage->id,
                'position' => 3,
            ]);
        }

        if ($termsPage) {
            NavigationItem::factory()->create([
                'menu_id' => $footerMenu->id,
                'type' => NavigationItemType::Page,
                'label' => 'Terms of Service',
                'url' => '/pages/terms',
                'resource_id' => $termsPage->id,
                'position' => 4,
            ]);
        }
    }

    private function seedAcmeElectronics(): void
    {
        $store = Store::query()->where('handle', 'acme-electronics')->first();
        if (! $store) {
            return;
        }

        $featured = Collection::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'featured')->first();
        $accessories = Collection::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'accessories')->first();

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

        if ($featured) {
            NavigationItem::factory()->create([
                'menu_id' => $mainMenu->id,
                'type' => NavigationItemType::Collection,
                'label' => 'Featured',
                'url' => '/collections/featured',
                'resource_id' => $featured->id,
                'position' => 1,
            ]);
        }

        if ($accessories) {
            NavigationItem::factory()->create([
                'menu_id' => $mainMenu->id,
                'type' => NavigationItemType::Collection,
                'label' => 'Accessories',
                'url' => '/collections/accessories',
                'resource_id' => $accessories->id,
                'position' => 2,
            ]);
        }
    }
}
