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

        $fashionCollections = Collection::query()->where('store_id', $fashion->getKey())->pluck('id', 'handle');
        $fashionPages = Page::query()->where('store_id', $fashion->getKey())->pluck('id', 'handle');
        $electronicsCollections = Collection::query()->where('store_id', $electronics->getKey())->pluck('id', 'handle');

        $this->seedMenu($fashion, 'main-menu', 'Main Menu', [
            ['label' => 'Home', 'type' => NavigationItemType::Link, 'url' => '/'],
            ['label' => 'New Arrivals', 'type' => NavigationItemType::Collection, 'resource_id' => $fashionCollections['new-arrivals']],
            ['label' => 'T-Shirts', 'type' => NavigationItemType::Collection, 'resource_id' => $fashionCollections['t-shirts']],
            ['label' => 'Pants & Jeans', 'type' => NavigationItemType::Collection, 'resource_id' => $fashionCollections['pants-jeans']],
            ['label' => 'Sale', 'type' => NavigationItemType::Collection, 'resource_id' => $fashionCollections['sale']],
        ]);

        $this->seedMenu($fashion, 'footer-menu', 'Footer Menu', [
            ['label' => 'About Us', 'type' => NavigationItemType::Page, 'resource_id' => $fashionPages['about']],
            ['label' => 'FAQ', 'type' => NavigationItemType::Page, 'resource_id' => $fashionPages['faq']],
            ['label' => 'Shipping & Returns', 'type' => NavigationItemType::Page, 'resource_id' => $fashionPages['shipping-returns']],
            ['label' => 'Privacy Policy', 'type' => NavigationItemType::Page, 'resource_id' => $fashionPages['privacy-policy']],
            ['label' => 'Terms of Service', 'type' => NavigationItemType::Page, 'resource_id' => $fashionPages['terms']],
        ]);

        $this->seedMenu($electronics, 'main-menu', 'Main Menu', [
            ['label' => 'Home', 'type' => NavigationItemType::Link, 'url' => '/'],
            ['label' => 'Featured', 'type' => NavigationItemType::Collection, 'resource_id' => $electronicsCollections['featured']],
            ['label' => 'Accessories', 'type' => NavigationItemType::Collection, 'resource_id' => $electronicsCollections['accessories']],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function seedMenu(Store $store, string $handle, string $title, array $items): void
    {
        $menu = NavigationMenu::query()->updateOrCreate(
            ['store_id' => $store->getKey(), 'handle' => $handle],
            ['title' => $title]
        );

        NavigationItem::query()->where('menu_id', $menu->getKey())->delete();

        foreach ($items as $position => $item) {
            NavigationItem::query()->create([
                'menu_id' => $menu->getKey(),
                'type' => $item['type'],
                'label' => $item['label'],
                'url' => $item['url'] ?? null,
                'resource_id' => $item['resource_id'] ?? null,
                'position' => $position,
            ]);
        }
    }
}
