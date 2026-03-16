<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

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

        NavigationItem::factory()->create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Collections',
            'url' => '/collections',
            'position' => 1,
        ]);

        $aboutPage = Page::query()->withoutGlobalScopes()->where('handle', 'about-us')->first();
        if ($aboutPage) {
            NavigationItem::factory()->forPage($aboutPage->id)->create([
                'menu_id' => $mainMenu->id,
                'label' => 'About Us',
                'position' => 2,
            ]);
        }

        $contactPage = Page::query()->withoutGlobalScopes()->where('handle', 'contact-us')->first();
        if ($contactPage) {
            NavigationItem::factory()->forPage($contactPage->id)->create([
                'menu_id' => $mainMenu->id,
                'label' => 'Contact',
                'position' => 3,
            ]);
        }

        $footerMenu = NavigationMenu::factory()->create([
            'store_id' => $store->id,
            'handle' => 'footer-menu',
            'title' => 'Footer Menu',
        ]);

        NavigationItem::factory()->create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Home',
            'url' => '/',
            'position' => 0,
        ]);

        $shippingPage = Page::query()->withoutGlobalScopes()->where('handle', 'shipping-policy')->first();
        if ($shippingPage) {
            NavigationItem::factory()->forPage($shippingPage->id)->create([
                'menu_id' => $footerMenu->id,
                'label' => 'Shipping Policy',
                'position' => 1,
            ]);
        }

        if ($aboutPage) {
            NavigationItem::factory()->forPage($aboutPage->id)->create([
                'menu_id' => $footerMenu->id,
                'label' => 'About Us',
                'position' => 2,
            ]);
        }

        if ($contactPage) {
            NavigationItem::factory()->forPage($contactPage->id)->create([
                'menu_id' => $footerMenu->id,
                'label' => 'Contact',
                'position' => 3,
            ]);
        }
    }
}
