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
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            $this->seedMenu($fashion, 'main-menu', 'Main Menu', [
                ['Home', 'link', '/', null],
                ...Collection::query()->where('store_id', $fashion->id)
                    ->whereIn('handle', ['new-arrivals', 't-shirts', 'pants-jeans', 'sale'])
                    ->get()
                    ->sortBy(fn (Collection $collection): int => array_search($collection->handle, ['new-arrivals', 't-shirts', 'pants-jeans', 'sale'], true))
                    ->map(fn (Collection $collection): array => [$collection->title, 'collection', null, $collection->id])
                    ->values()
                    ->all(),
            ]);

            $this->seedMenu($fashion, 'footer-menu', 'Footer Menu',
                Page::query()->where('store_id', $fashion->id)
                    ->whereIn('handle', ['about', 'faq', 'shipping-returns', 'privacy-policy', 'terms'])
                    ->get()
                    ->sortBy(fn (Page $page): int => array_search($page->handle, ['about', 'faq', 'shipping-returns', 'privacy-policy', 'terms'], true))
                    ->map(fn (Page $page): array => [$page->title, 'page', null, $page->id])
                    ->values()
                    ->all(),
            );

            $this->seedMenu($electronics, 'main-menu', 'Main Menu', [
                ['Home', 'link', '/', null],
                ...Collection::query()->where('store_id', $electronics->id)
                    ->whereIn('handle', ['featured', 'accessories'])
                    ->get()
                    ->sortBy(fn (Collection $collection): int => array_search($collection->handle, ['featured', 'accessories'], true))
                    ->map(fn (Collection $collection): array => [$collection->title, 'collection', null, $collection->id])
                    ->values()
                    ->all(),
            ]);
        });
    }

    /**
     * @param  array<int, array{string, string, ?string, ?int}>  $items
     */
    private function seedMenu(Store $store, string $handle, string $title, array $items): void
    {
        $menu = NavigationMenu::query()->updateOrCreate(
            ['store_id' => $store->id, 'handle' => $handle],
            ['title' => $title],
        );

        foreach ($items as $position => [$label, $type, $url, $resourceId]) {
            NavigationItem::query()->updateOrCreate(
                ['menu_id' => $menu->id, 'position' => $position],
                ['type' => $type, 'label' => $label, 'url' => $url, 'resource_id' => $resourceId],
            );
        }
    }
}
