<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * @return array<int, array{id: int, label: string, url: string, type: string}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $storeId = $menu->store_id;

        return Cache::remember(
            "navigation_tree:{$storeId}:{$menu->id}",
            300,
            function () use ($menu) {
                return $menu->items->map(function (NavigationItem $item) {
                    return [
                        'id' => $item->id,
                        'label' => $item->label,
                        'url' => $this->resolveUrl($item),
                        'type' => $item->type->value,
                    ];
                })->all();
            }
        );
    }

    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url ?? '/',
            NavigationItemType::Page => $this->resolvePageUrl($item->resource_id),
            NavigationItemType::Collection => $this->resolveCollectionUrl($item->resource_id),
            NavigationItemType::Product => $this->resolveProductUrl($item->resource_id),
        };
    }

    private function resolvePageUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '/';
        }

        $page = Page::withoutGlobalScopes()->find($resourceId);

        return $page ? "/pages/{$page->handle}" : '/';
    }

    private function resolveCollectionUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '/collections';
        }

        $collection = \App\Models\Collection::withoutGlobalScopes()->find($resourceId);

        return $collection ? "/collections/{$collection->handle}" : '/collections';
    }

    private function resolveProductUrl(?int $resourceId): string
    {
        if (! $resourceId) {
            return '/';
        }

        $product = \App\Models\Product::withoutGlobalScopes()->find($resourceId);

        return $product ? "/products/{$product->handle}" : '/';
    }
}
