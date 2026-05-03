<?php

namespace Database\Seeders;

use App\Models\Collection as ProductCollection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::query()->get()->each(function (Store $store): void {
            $menus = NavigationMenu::withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->get()
                ->keyBy('handle');

            $this->replaceItems($menus->get('main-menu'), $this->mainMenuItems($store));
            $this->replaceItems($menus->get('footer-menu'), $this->footerMenuItems($store));
        });
    }

    /**
     * @param  array<int, array{label: string, type: string, url?: string|null, resource_id?: int|null}>  $items
     */
    private function replaceItems(?NavigationMenu $menu, array $items): void
    {
        if ($menu === null) {
            return;
        }

        NavigationItem::withoutGlobalScopes()
            ->where('menu_id', $menu->getKey())
            ->delete();

        foreach ($items as $position => $item) {
            NavigationItem::withoutGlobalScopes()->create([
                'menu_id' => $menu->getKey(),
                'type' => $item['type'],
                'label' => $item['label'],
                'url' => $item['url'] ?? null,
                'resource_id' => $item['resource_id'] ?? null,
                'position' => $position,
            ]);
        }
    }

    /**
     * @return array<int, array{label: string, type: string, url?: string|null, resource_id?: int|null}>
     */
    private function mainMenuItems(Store $store): array
    {
        $newArrivals = $this->collection($store, 'new-arrivals') ?? $this->collection($store, 'featured');
        $secondaryCollection = $this->collection($store, 't-shirts') ?? $this->collection($store, 'accessories');
        $about = $this->page($store, 'about');

        return array_values(array_filter([
            ['label' => 'Collections', 'type' => 'link', 'url' => '/collections'],
            $newArrivals ? ['label' => $newArrivals->title, 'type' => 'collection', 'resource_id' => $newArrivals->getKey()] : null,
            $secondaryCollection ? ['label' => $secondaryCollection->title, 'type' => 'collection', 'resource_id' => $secondaryCollection->getKey()] : null,
            ['label' => 'Search', 'type' => 'link', 'url' => '/search'],
            $about ? ['label' => 'About', 'type' => 'page', 'resource_id' => $about->getKey()] : null,
        ]));
    }

    /**
     * @return array<int, array{label: string, type: string, url?: string|null, resource_id?: int|null}>
     */
    private function footerMenuItems(Store $store): array
    {
        $sale = $this->collection($store, 'sale');
        $about = $this->page($store, 'about');
        $faq = $this->page($store, 'faq');

        return array_values(array_filter([
            $sale ? ['label' => 'Sale', 'type' => 'collection', 'resource_id' => $sale->getKey()] : null,
            $about ? ['label' => 'About', 'type' => 'page', 'resource_id' => $about->getKey()] : null,
            $faq ? ['label' => 'FAQ', 'type' => 'page', 'resource_id' => $faq->getKey()] : null,
            ['label' => 'Account', 'type' => 'link', 'url' => '/account'],
        ]));
    }

    private function collection(Store $store, string $handle): ?ProductCollection
    {
        return ProductCollection::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('handle', $handle)
            ->first();
    }

    private function page(Store $store, string $handle): ?Page
    {
        return Page::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('handle', $handle)
            ->first();
    }
}
