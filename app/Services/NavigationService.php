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
     * Cache lifetime for resolved menu trees (5 minutes).
     */
    public const TTL_SECONDS = 300;

    /**
     * Get the cached item tree for the menu with the given handle in the
     * current store. Returns an empty array when no store is bound or the
     * menu does not exist.
     *
     * @return array<int, array{id: int, label: string, url: string, type: NavigationItemType, children: array}>
     */
    public function forHandle(string $handle): array
    {
        if (! app()->bound('current_store')) {
            return [];
        }

        $storeId = (int) app('current_store')->getKey();

        return Cache::remember(
            $this->cacheKey($storeId, $handle),
            self::TTL_SECONDS,
            function () use ($handle): array {
                $menu = NavigationMenu::query()->where('handle', $handle)->first();

                return $menu === null ? [] : $this->buildTree($menu);
            },
        );
    }

    /**
     * Build the navigation tree for a menu: a flat list of resolved items
     * ordered by position (nesting is not modelled in the schema, so every
     * item carries an empty `children` list for forward compatibility).
     *
     * @return array<int, array{id: int, label: string, url: string, type: NavigationItemType, children: array}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $items = $menu->items;
        $handles = $this->prefetchResourceHandles($items);

        return $items->map(fn (NavigationItem $item): array => [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $this->resolveUrl($item, $handles),
            'type' => $item->type,
            'children' => [],
        ])->all();
    }

    /**
     * Resolve the storefront URL of a navigation item based on its type.
     *
     * @param  array<string, array<int, string>>|null  $handles  Prefetched resource handles keyed by type, then resource id.
     */
    public function resolveUrl(NavigationItem $item, ?array $handles = null): string
    {
        if ($item->type === NavigationItemType::Link) {
            return $item->url ?? '#';
        }

        $handle = $handles[$item->type->value][$item->resource_id] ?? $this->lookupResourceHandle($item);

        if ($handle === null) {
            return '#';
        }

        return match ($item->type) {
            NavigationItemType::Page => "/pages/{$handle}",
            NavigationItemType::Collection => "/collections/{$handle}",
            NavigationItemType::Product => "/products/{$handle}",
            default => '#',
        };
    }

    /**
     * Forget the cached tree of a store's menu.
     */
    public function invalidate(?int $storeId, string $menuHandle): void
    {
        if ($storeId === null) {
            return;
        }

        Cache::forget($this->cacheKey($storeId, $menuHandle));
    }

    /**
     * Look up the handle of the resource a page/collection/product item
     * points to. Returns null when the resource no longer exists.
     */
    private function lookupResourceHandle(NavigationItem $item): ?string
    {
        if ($item->resource_id === null) {
            return null;
        }

        $model = match ($item->type) {
            NavigationItemType::Page => Page::class,
            NavigationItemType::Collection => Collection::class,
            NavigationItemType::Product => Product::class,
            default => null,
        };

        if ($model === null) {
            return null;
        }

        return $model::query()->whereKey($item->resource_id)->value('handle');
    }

    /**
     * Bulk-load the handles of all resources referenced by the items to
     * avoid one query per item.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, NavigationItem>  $items
     * @return array<string, array<int, string>>
     */
    private function prefetchResourceHandles(\Illuminate\Database\Eloquent\Collection $items): array
    {
        $handles = [];

        $resourceItems = $items
            ->filter(fn (NavigationItem $item): bool => $item->type !== NavigationItemType::Link && $item->resource_id !== null)
            ->groupBy(fn (NavigationItem $item): string => $item->type->value);

        foreach ($resourceItems as $type => $group) {
            $model = match ($type) {
                'page' => Page::class,
                'collection' => Collection::class,
                'product' => Product::class,
                default => null,
            };

            if ($model === null) {
                continue;
            }

            $handles[$type] = $model::query()
                ->whereIn('id', $group->pluck('resource_id')->all())
                ->pluck('handle', 'id')
                ->all();
        }

        return $handles;
    }

    /**
     * Cache key for a store's menu tree.
     */
    private function cacheKey(int $storeId, string $menuHandle): string
    {
        return "nav:{$storeId}:{$menuHandle}";
    }
}
