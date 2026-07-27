<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationSeeder extends Seeder
{
    /**
     * Create the navigation menus and items (spec 07 §3.16).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            $fashionMain = $this->menu($fashion->id, 'main-menu', 'Main Menu');
            $this->seedItems($fashionMain, [
                ['Home', NavigationItemType::Link, '/', null],
                ['New Arrivals', NavigationItemType::Collection, null, $this->collectionId($fashion->id, 'new-arrivals')],
                ['T-Shirts', NavigationItemType::Collection, null, $this->collectionId($fashion->id, 't-shirts')],
                ['Pants & Jeans', NavigationItemType::Collection, null, $this->collectionId($fashion->id, 'pants-jeans')],
                ['Sale', NavigationItemType::Collection, null, $this->collectionId($fashion->id, 'sale')],
            ]);

            $fashionFooter = $this->menu($fashion->id, 'footer-menu', 'Footer Menu');
            $this->seedItems($fashionFooter, [
                ['About Us', NavigationItemType::Page, null, $this->pageId($fashion->id, 'about')],
                ['FAQ', NavigationItemType::Page, null, $this->pageId($fashion->id, 'faq')],
                ['Shipping & Returns', NavigationItemType::Page, null, $this->pageId($fashion->id, 'shipping-returns')],
                ['Privacy Policy', NavigationItemType::Page, null, $this->pageId($fashion->id, 'privacy-policy')],
                ['Terms of Service', NavigationItemType::Page, null, $this->pageId($fashion->id, 'terms')],
            ]);

            $electronicsMain = $this->menu($electronics->id, 'main-menu', 'Main Menu');
            $this->seedItems($electronicsMain, [
                ['Home', NavigationItemType::Link, '/', null],
                ['Featured', NavigationItemType::Collection, null, $this->collectionId($electronics->id, 'featured')],
                ['Accessories', NavigationItemType::Collection, null, $this->collectionId($electronics->id, 'accessories')],
            ]);
        });
    }

    /**
     * Create or update one menu.
     */
    private function menu(int $storeId, string $handle, string $title): NavigationMenu
    {
        return NavigationMenu::query()->updateOrCreate(
            ['store_id' => $storeId, 'handle' => $handle],
            ['title' => $title],
        );
    }

    /**
     * Replace the menu's items with the given definitions.
     *
     * @param  list<array{0: string, 1: NavigationItemType, 2: string|null, 3: int|null}>  $items
     */
    private function seedItems(NavigationMenu $menu, array $items): void
    {
        $menu->items()->delete();

        foreach ($items as $position => [$label, $type, $url, $resourceId]) {
            $menu->items()->create([
                'type' => $type,
                'label' => $label,
                'url' => $url,
                'resource_id' => $resourceId,
                'position' => $position,
            ]);
        }
    }

    /**
     * Resolve a collection id by handle.
     */
    private function collectionId(int $storeId, string $handle): int
    {
        return Collection::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('handle', $handle)
            ->firstOrFail()
            ->id;
    }

    /**
     * Resolve a page id by handle.
     */
    private function pageId(int $storeId, string $handle): int
    {
        return Page::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('handle', $handle)
            ->firstOrFail()
            ->id;
    }
}
