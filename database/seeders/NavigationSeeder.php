<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    /**
     * Seed the main and footer navigation menus for both demo stores.
     */
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $this->seedMenu($fashion, 'main-menu', 'Main Menu', [
            ['label' => 'Home', 'type' => NavigationItemType::Link, 'url' => '/'],
            ['label' => 'New Arrivals', 'type' => NavigationItemType::Collection, 'handle' => 'new-arrivals'],
            ['label' => 'T-Shirts', 'type' => NavigationItemType::Collection, 'handle' => 't-shirts'],
            ['label' => 'Pants & Jeans', 'type' => NavigationItemType::Collection, 'handle' => 'pants-jeans'],
            ['label' => 'Sale', 'type' => NavigationItemType::Collection, 'handle' => 'sale'],
        ]);

        $this->seedMenu($fashion, 'footer-menu', 'Footer Menu', [
            ['label' => 'About Us', 'type' => NavigationItemType::Page, 'handle' => 'about'],
            ['label' => 'FAQ', 'type' => NavigationItemType::Page, 'handle' => 'faq'],
            ['label' => 'Shipping & Returns', 'type' => NavigationItemType::Page, 'handle' => 'shipping-returns'],
            ['label' => 'Privacy Policy', 'type' => NavigationItemType::Page, 'handle' => 'privacy-policy'],
            ['label' => 'Terms of Service', 'type' => NavigationItemType::Page, 'handle' => 'terms'],
        ]);

        $this->seedMenu($electronics, 'main-menu', 'Main Menu', [
            ['label' => 'Home', 'type' => NavigationItemType::Link, 'url' => '/'],
            ['label' => 'Featured', 'type' => NavigationItemType::Collection, 'handle' => 'featured'],
            ['label' => 'Accessories', 'type' => NavigationItemType::Collection, 'handle' => 'accessories'],
        ]);
    }

    /**
     * @param  list<array{label: string, type: NavigationItemType, url?: string, handle?: string}>  $items
     */
    private function seedMenu(Store $store, string $handle, string $title, array $items): void
    {
        $menu = NavigationMenu::query()->updateOrCreate(
            [
                'store_id' => $store->getKey(),
                'handle' => $handle,
            ],
            ['title' => $title],
        );

        foreach ($items as $position => $item) {
            $resourceId = match ($item['type']) {
                NavigationItemType::Collection => Collection::query()
                    ->withoutGlobalScopes()
                    ->where('store_id', $store->getKey())
                    ->where('handle', $item['handle'])
                    ->value('id'),
                NavigationItemType::Page => Page::query()
                    ->withoutGlobalScopes()
                    ->where('store_id', $store->getKey())
                    ->where('handle', $item['handle'])
                    ->value('id'),
                default => null,
            };

            $menu->items()->updateOrCreate(
                ['label' => $item['label']],
                [
                    'type' => $item['type'],
                    'url' => $item['url'] ?? null,
                    'resource_id' => $resourceId,
                    'position' => $position,
                ],
            );
        }
    }
}
