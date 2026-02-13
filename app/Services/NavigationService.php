<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * Build a navigation tree from a menu.
     *
     * @return array<int, array{id: int, label: string, url: string, type: string, position: int}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $cacheKey = 'nav_tree:'.$menu->store_id.':'.$menu->handle;

        return Cache::remember($cacheKey, 300, function () use ($menu): array {
            return $menu->items
                ->map(fn (NavigationItem $item): array => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'url' => $this->resolveUrl($item),
                    'type' => $item->type->value,
                    'position' => $item->position,
                ])
                ->all();
        });
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

    /**
     * Resolve a page URL by resource ID.
     */
    protected function resolvePageUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $page = Page::withoutGlobalScopes()->find($resourceId);

        return $page ? route('storefront.pages.show', $page->handle) : '#';
    }

    /**
     * Resolve a collection URL by resource ID.
     */
    protected function resolveCollectionUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $collection = Collection::withoutGlobalScopes()->find($resourceId);

        return $collection ? route('storefront.collections.show', $collection->handle) : '#';
    }

    /**
     * Resolve a product URL by resource ID.
     */
    protected function resolveProductUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $product = Product::withoutGlobalScopes()->find($resourceId);

        return $product ? route('storefront.products.show', $product->handle) : '#';
    }
}
