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
     * Build a hierarchical tree from flat navigation items.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $cacheKey = "navigation_tree:{$menu->id}";

        return Cache::remember($cacheKey, 300, function () use ($menu) {
            $items = $menu->items()->orderBy('position')->get();

            return $items->map(function (NavigationItem $item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label,
                    'url' => $this->resolveUrl($item),
                    'type' => $item->type->value,
                    'position' => $item->position,
                ];
            })->all();
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

    private function resolvePageUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $page = Page::withoutGlobalScopes()->find($resourceId);

        return $page ? '/pages/'.$page->handle : '#';
    }

    private function resolveCollectionUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $collection = Collection::withoutGlobalScopes()->find($resourceId);

        return $collection ? '/collections/'.$collection->handle : '#';
    }

    private function resolveProductUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '#';
        }

        $product = Product::withoutGlobalScopes()->find($resourceId);

        return $product ? '/products/'.$product->handle : '#';
    }
}
