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
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get();

        foreach ($stores as $store) {
            $menu = NavigationMenu::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'handle' => 'main-menu',
                ],
                [
                    'title' => 'Main Menu',
                ]
            );

            $aboutPage = Page::query()->where('store_id', $store->id)->where('handle', 'about')->first();
            $featuredCollection = Collection::query()->where('store_id', $store->id)->first();

            $this->upsertItem($menu->id, 0, 'link', 'Home', '/');
            $this->upsertItem($menu->id, 1, 'collection', 'Shop', null, $featuredCollection?->id);
            $this->upsertItem($menu->id, 2, 'page', 'About', null, $aboutPage?->id);
            $this->upsertItem($menu->id, 3, 'link', 'Contact', '/pages/contact');
        }
    }

    private function upsertItem(
        int $menuId,
        int $position,
        string $type,
        string $label,
        ?string $url,
        ?int $resourceId = null
    ): void {
        NavigationItem::query()->updateOrCreate(
            [
                'menu_id' => $menuId,
                'position' => $position,
            ],
            [
                'type' => $type,
                'label' => $label,
                'url' => $url,
                'resource_id' => $resourceId,
            ]
        );
    }
}
