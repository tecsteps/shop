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
     * Seed navigation menus and items for each store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedFashionMenus();
            $this->seedElectronicsMenus();
        });
    }

    private function seedFashionMenus(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $collections = Collection::where('store_id', $store->id)->get()->keyBy('handle');
        $pages = Page::where('store_id', $store->id)->get()->keyBy('handle');

        $this->seedMenu($store->id, 'main-menu', 'Main Menu', [
            ['label' => 'Home', 'type' => 'link', 'url' => '/'],
            ['label' => 'New Arrivals', 'type' => 'collection', 'resource_id' => $collections['new-arrivals']->id],
            ['label' => 'T-Shirts', 'type' => 'collection', 'resource_id' => $collections['t-shirts']->id],
            ['label' => 'Pants & Jeans', 'type' => 'collection', 'resource_id' => $collections['pants-jeans']->id],
            ['label' => 'Sale', 'type' => 'collection', 'resource_id' => $collections['sale']->id],
        ]);

        $this->seedMenu($store->id, 'footer-menu', 'Footer Menu', [
            ['label' => 'About Us', 'type' => 'page', 'resource_id' => $pages['about']->id],
            ['label' => 'FAQ', 'type' => 'page', 'resource_id' => $pages['faq']->id],
            ['label' => 'Shipping & Returns', 'type' => 'page', 'resource_id' => $pages['shipping-returns']->id],
            ['label' => 'Privacy Policy', 'type' => 'page', 'resource_id' => $pages['privacy-policy']->id],
            ['label' => 'Terms of Service', 'type' => 'page', 'resource_id' => $pages['terms']->id],
        ]);
    }

    private function seedElectronicsMenus(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();

        $collections = Collection::where('store_id', $store->id)->get()->keyBy('handle');

        $this->seedMenu($store->id, 'main-menu', 'Main Menu', [
            ['label' => 'Home', 'type' => 'link', 'url' => '/'],
            ['label' => 'Featured', 'type' => 'collection', 'resource_id' => $collections['featured']->id],
            ['label' => 'Accessories', 'type' => 'collection', 'resource_id' => $collections['accessories']->id],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function seedMenu(int $storeId, string $handle, string $title, array $items): void
    {
        $menu = NavigationMenu::updateOrCreate(
            ['store_id' => $storeId, 'handle' => $handle],
            ['title' => $title],
        );

        $menu->items()->delete();

        foreach ($items as $position => $item) {
            NavigationItem::create([
                'menu_id' => $menu->id,
                'type' => $item['type'],
                'label' => $item['label'],
                'url' => $item['url'] ?? null,
                'resource_id' => $item['resource_id'] ?? null,
                'position' => $position,
            ]);
        }
    }
}
