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
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->sole();
            $electronics = Store::query()->where('handle', 'acme-electronics')->sole();
            $this->menu($fashion, 'main-menu', 'Main Menu', [
                ['Home', 'link', '/', null],
                ...collect(['new-arrivals' => 'New Arrivals', 't-shirts' => 'T-Shirts', 'pants-jeans' => 'Pants & Jeans', 'sale' => 'Sale'])
                    ->map(fn (string $label, string $handle): array => [$label, 'collection', null, Collection::withoutGlobalScopes()->where('store_id', $fashion->id)->where('handle', $handle)->valueOrFail('id')])->values()->all(),
            ]);
            $this->menu($fashion, 'footer-menu', 'Footer Menu', collect([
                'about' => 'About Us', 'faq' => 'FAQ', 'shipping-returns' => 'Shipping & Returns', 'privacy-policy' => 'Privacy Policy', 'terms' => 'Terms of Service',
            ])->map(fn (string $label, string $handle): array => [$label, 'page', null, Page::withoutGlobalScopes()->where('store_id', $fashion->id)->where('handle', $handle)->valueOrFail('id')])->values()->all());
            $this->menu($electronics, 'main-menu', 'Main Menu', [
                ['Home', 'link', '/', null],
                ...collect(['featured' => 'Featured', 'accessories' => 'Accessories'])
                    ->map(fn (string $label, string $handle): array => [$label, 'collection', null, Collection::withoutGlobalScopes()->where('store_id', $electronics->id)->where('handle', $handle)->valueOrFail('id')])->values()->all(),
            ]);
        });
    }

    /** @param list<array{0: string, 1: string, 2: ?string, 3: ?int}> $items */
    private function menu(Store $store, string $handle, string $title, array $items): void
    {
        $menu = NavigationMenu::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'handle' => $handle], ['title' => $title]);
        foreach ($items as $position => [$label, $type, $url, $resourceId]) {
            NavigationItem::query()->updateOrCreate(
                ['menu_id' => $menu->id, 'position' => $position],
                ['label' => $label, 'type' => $type, 'url' => $url, 'resource_id' => $resourceId],
            );
        }
    }
}
