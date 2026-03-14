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
     * Build a nested navigation tree from flat items using parent_id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $storeId = $menu->store_id;
        $cacheKey = "navigation:{$storeId}:{$menu->handle}";

        return Cache::remember($cacheKey, 300, function () use ($menu) {
            $items = $menu->items()->orderBy('position')->get();

            $grouped = $items->groupBy(fn (NavigationItem $item) => $item->parent_id ?? 0);

            return $this->buildChildren($grouped, 0);
        });
    }

    /**
     * Resolve the URL for a navigation item based on its type.
     */
    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url ?? '/',
            NavigationItemType::Page => $this->resolvePageUrl($item),
            NavigationItemType::Collection => $this->resolveCollectionUrl($item),
            NavigationItemType::Product => $this->resolveProductUrl($item),
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, \Illuminate\Support\Collection<int, NavigationItem>>  $grouped
     * @return array<int, array<string, mixed>>
     */
    private function buildChildren(\Illuminate\Support\Collection $grouped, int|string $parentId): array
    {
        $children = $grouped->get($parentId, collect());

        return $children->map(function (NavigationItem $item) use ($grouped) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $this->resolveUrl($item),
                'type' => $item->type->value,
                'children' => $this->buildChildren($grouped, $item->id),
            ];
        })->values()->all();
    }

    private function resolvePageUrl(NavigationItem $item): string
    {
        $page = Page::withoutGlobalScopes()->find($item->resource_id);

        return $page ? "/pages/{$page->handle}" : '/';
    }

    private function resolveCollectionUrl(NavigationItem $item): string
    {
        $collection = Collection::withoutGlobalScopes()->find($item->resource_id);

        return $collection ? "/collections/{$collection->handle}" : '/';
    }

    private function resolveProductUrl(NavigationItem $item): string
    {
        $product = Product::withoutGlobalScopes()->find($item->resource_id);

        return $product ? "/products/{$product->handle}" : '/';
    }
}
