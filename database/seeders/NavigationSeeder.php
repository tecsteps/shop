<?php

namespace Database\Seeders;

use App\Models\Collection;
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
        $main = NavigationMenu::withoutGlobalScopes()->updateOrCreate(['store_id' => $fashion->id, 'handle' => 'main-menu'], ['title' => 'Main Menu']);
        $this->replace($main, [['Home', 'link', '/', null], ...collect(['new-arrivals' => 'New Arrivals', 't-shirts' => 'T-Shirts', 'pants-jeans' => 'Pants & Jeans', 'sale' => 'Sale'])->map(fn ($label, $handle) => [$label, 'collection', null, Collection::withoutGlobalScopes()->where('store_id', $fashion->id)->where('handle', $handle)->value('id')])->values()->all()]);

        $footer = NavigationMenu::withoutGlobalScopes()->updateOrCreate(['store_id' => $fashion->id, 'handle' => 'footer-menu'], ['title' => 'Footer Menu']);
        $this->replace($footer, collect(['about' => 'About Us', 'faq' => 'FAQ', 'shipping-returns' => 'Shipping & Returns', 'privacy-policy' => 'Privacy Policy', 'terms' => 'Terms of Service'])->map(fn ($label, $handle) => [$label, 'page', null, Page::withoutGlobalScopes()->where('store_id', $fashion->id)->where('handle', $handle)->value('id')])->values()->all());

        $electronicsMain = NavigationMenu::withoutGlobalScopes()->updateOrCreate(['store_id' => $electronics->id, 'handle' => 'main-menu'], ['title' => 'Main Menu']);
        $this->replace($electronicsMain, [['Home', 'link', '/', null], ...collect(['featured' => 'Featured', 'accessories' => 'Accessories'])->map(fn ($label, $handle) => [$label, 'collection', null, Collection::withoutGlobalScopes()->where('store_id', $electronics->id)->where('handle', $handle)->value('id')])->values()->all()]);
    }

    /** @param list<array{0: string, 1: string, 2: ?string, 3: ?int}> $items */
    private function replace(NavigationMenu $menu, array $items): void
    {
        $menu->items()->delete();
        foreach ($items as $position => [$label, $type, $url, $resourceId]) {
            $menu->items()->create(['type' => $type, 'label' => $label, 'url' => $url, 'resource_id' => $resourceId, 'position' => $position]);
        }
    }
}
