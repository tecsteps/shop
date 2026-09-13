<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;

/**
 * Storefront navigation tree building and URL resolution per roadmap 3.5.
 *
 * Navigation items are a flat ordered list (no nesting column in schema);
 * buildTree returns the ordered items with resolved URLs.
 */
class NavigationService
{
    public function menu(Store $store, string $handle): ?NavigationMenu
    {
        $menuId = Cache::remember(
            "navigation_menu:{$store->getKey()}:{$handle}",
            now()->addMinutes(5),
            fn (): ?int => NavigationMenu::query()
                ->where('store_id', $store->getKey())
                ->where('handle', $handle)
                ->value('id')
        );

        if ($menuId === null) {
            return null;
        }

        return NavigationMenu::query()
            ->whereKey($menuId)
            ->with(['navigationItems' => fn ($query) => $query->orderBy('position')->orderBy('id')])
            ->first();
    }

    /**
     * @return array<int, array{label: string, url: string, type: string}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        return Cache::remember(
            "navigation_tree:{$menu->store_id}:{$menu->getKey()}",
            now()->addMinutes(5),
            function () use ($menu): array {
                $items = $menu->relationLoaded('navigationItems')
                    ? $menu->navigationItems
                    : $menu->navigationItems()->orderBy('position')->orderBy('id')->get();

                return $items->map(fn (NavigationItem $item): array => [
                    'label' => $item->label,
                    'url' => $this->resolveUrl($item),
                    'type' => $item->type->value,
                ])->all();
            }
        );
    }

    public function resolveUrl(NavigationItem $item): string
    {
        $type = $item->type->value;

        if ($type === 'link') {
            return (string) ($item->url ?? '#');
        }

        if ($item->resource_id === null) {
            return '#';
        }

        $storeId = $item->menu?->store_id;

        if ($type === 'page') {
            $page = \App\Models\Page::query()
                ->when($storeId !== null, fn ($query) => $query->where('store_id', $storeId))
                ->whereKey($item->resource_id)
                ->first();

            return $page !== null ? route('storefront.pages.show', ['handle' => $page->handle]) : '#';
        }

        if ($type === 'collection') {
            $collection = \App\Models\Collection::query()
                ->when($storeId !== null, fn ($query) => $query->where('store_id', $storeId))
                ->whereKey($item->resource_id)
                ->first();

            return $collection !== null ? route('storefront.collections.show', ['handle' => $collection->handle]) : '#';
        }

        if ($type === 'product') {
            $product = \App\Models\Product::query()
                ->when($storeId !== null, fn ($query) => $query->where('store_id', $storeId))
                ->whereKey($item->resource_id)
                ->first();

            return $product !== null ? route('storefront.products.show', ['handle' => $product->handle]) : '#';
        }

        return '#';
    }
}
