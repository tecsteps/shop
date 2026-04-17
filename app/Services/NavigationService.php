<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * Build a flat list of navigation items for a menu, resolved with URLs.
     *
     * @return array<int, array{label: string, url: string, type: string}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $storeId = $menu->store_id;

        return Cache::remember(
            "navigation_tree:{$storeId}:{$menu->id}",
            300,
            function () use ($menu): array {
                $items = $menu->items()->orderBy('position')->get();

                return $items->map(fn (NavigationItem $item) => [
                    'label' => $item->label,
                    'url' => $this->resolveUrl($item),
                    'type' => $item->type->value,
                ])->all();
            }
        );
    }

    /**
     * Resolve the URL for a navigation item based on its type.
     */
    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url ?? '#',
            NavigationItemType::Page => $this->resolvePageUrl($item->resource_id),
            NavigationItemType::Collection => $this->resolveCollectionUrl($item->resource_id),
            NavigationItemType::Product => $this->resolveProductUrl($item->resource_id),
        };
    }

    protected function resolvePageUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $page = Page::query()->withoutGlobalScopes()->find($resourceId);

        return $page ? '/pages/'.$page->handle : '#';
    }

    protected function resolveCollectionUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        if (! class_exists(\App\Models\Collection::class)) {
            return '/collections/'.$resourceId;
        }

        $collection = \App\Models\Collection::query()->withoutGlobalScopes()->find($resourceId);

        return $collection ? '/collections/'.$collection->handle : '#';
    }

    protected function resolveProductUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        if (! class_exists(\App\Models\Product::class)) {
            return '/products/'.$resourceId;
        }

        $product = \App\Models\Product::query()->withoutGlobalScopes()->find($resourceId);

        return $product ? '/products/'.$product->handle : '#';
    }

    /**
     * Clear navigation cache for a store.
     */
    public function clearCache(Store $store): void
    {
        $menus = NavigationMenu::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get();

        foreach ($menus as $menu) {
            Cache::forget("navigation_tree:{$store->id}:{$menu->id}");
        }
    }
}
