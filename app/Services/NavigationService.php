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
     * @return array<int, array{label: string, url: string}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $storeId = $menu->store_id;
        $cacheKey = "navigation:{$storeId}:{$menu->handle}";

        return Cache::remember($cacheKey, 300, function () use ($menu) {
            return $menu->items->map(fn (NavigationItem $item) => [
                'label' => $item->label,
                'url' => $this->resolveUrl($item),
            ])->all();
        });
    }

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

        $page = Page::withoutGlobalScopes()->find($resourceId);

        return $page ? '/pages/'.$page->handle : '#';
    }

    protected function resolveCollectionUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $collection = Collection::withoutGlobalScopes()->find($resourceId);

        return $collection ? '/collections/'.$collection->handle : '#';
    }

    protected function resolveProductUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $product = Product::withoutGlobalScopes()->find($resourceId);

        return $product ? '/products/'.$product->handle : '#';
    }
}
