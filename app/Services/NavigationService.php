<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * @return list<array{id: int, label: string, url: string, children: list<mixed>}>
     */
    public function getMenuTree(string $handle, Store $store): array
    {
        $cacheKey = "nav_menu:{$store->id}:{$handle}";

        return Cache::remember($cacheKey, 300, function () use ($handle, $store): array {
            $menu = NavigationMenu::where('store_id', $store->id)
                ->where('handle', $handle)
                ->first();

            if (! $menu) {
                return [];
            }

            $items = $menu->items()->orderBy('position')->get();

            return $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label,
                    'url' => $this->resolveItemUrl($item),
                ];
            })->all();
        });
    }

    public function clearCache(Store $store): void
    {
        $menus = NavigationMenu::where('store_id', $store->id)->get();

        foreach ($menus as $menu) {
            Cache::forget("nav_menu:{$store->id}:{$menu->handle}");
        }
    }

    private function resolveItemUrl(\App\Models\NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url ?? '#',
            NavigationItemType::Page => $this->resolvePageUrl($item->resource_id),
            NavigationItemType::Collection => $this->resolveCollectionUrl($item->resource_id),
            NavigationItemType::Product => $this->resolveProductUrl($item->resource_id),
        };
    }

    private function resolvePageUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $page = Page::find($resourceId);

        return $page ? "/pages/{$page->handle}" : '#';
    }

    private function resolveCollectionUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $collection = Collection::find($resourceId);

        return $collection ? "/collections/{$collection->handle}" : '#';
    }

    private function resolveProductUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $product = Product::find($resourceId);

        return $product ? "/products/{$product->handle}" : '#';
    }
}
