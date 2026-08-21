<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get() as $store) {
            $menu = NavigationMenu::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->getKey(), 'handle' => 'main'],
                ['name' => 'Main navigation', 'title' => 'Main navigation'],
            );
            $about = Page::withoutGlobalScopes()->where('store_id', $store->getKey())->where('handle', 'about')->first();
            $newArrivals = Collection::withoutGlobalScopes()->where('store_id', $store->getKey())->where('handle', 'new-arrivals')->first();
            $menu->items()->delete();
            $menu->items()->createMany(array_values(array_filter([
                ['label' => 'Collections', 'type' => 'url', 'url' => '/collections', 'position' => 1],
                $newArrivals === null ? null : ['label' => 'New arrivals', 'type' => 'collection', 'resource_id' => $newArrivals->getKey(), 'position' => 2],
                $about === null ? null : ['label' => 'About', 'type' => 'page', 'resource_id' => $about->getKey(), 'position' => 3],
            ])));
        }
    }
}
