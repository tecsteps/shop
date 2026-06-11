<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Builds navigation trees from navigation_menus / navigation_items and
 * resolves item URLs for link, page, collection, and product item types.
 * Trees are cached per store with a five-minute TTL.
 */
class NavigationService
{
    private const int CACHE_TTL_MINUTES = 5;

    /**
     * The cached navigation tree for a menu handle of the current store.
     * Returns an empty array when no store is bound or the menu is missing.
     *
     * @return list<array{id: int, label: string, url: string, type: string, children: list<mixed>}>
     */
    public function tree(string $handle): array
    {
        if (! app()->bound('current_store')) {
            return [];
        }

        $storeId = app('current_store')->getKey();

        return Cache::remember(
            "navigation_tree:{$storeId}:{$handle}",
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($storeId, $handle): array {
                $menu = NavigationMenu::query()
                    ->withoutGlobalScopes()
                    ->where('store_id', $storeId)
                    ->where('handle', $handle)
                    ->first();

                return $menu === null ? [] : $this->buildTree($menu);
            },
        );
    }

    /**
     * Build the navigation tree for a menu. Items whose linked resource no
     * longer exists are omitted. Resource handles are resolved in bulk to
     * avoid per-item queries.
     *
     * @return list<array{id: int, label: string, url: string, type: string, children: list<mixed>}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $items = $menu->items()->get();

        $handleMaps = $this->resourceHandleMaps($menu, $items->all());

        $tree = [];

        foreach ($items as $item) {
            $url = $this->resolveUrlUsing($item, $handleMaps);

            if ($url === null) {
                continue;
            }

            $tree[] = [
                'id' => $item->getKey(),
                'label' => $item->label,
                'url' => $url,
                'type' => $item->type->value,
                'children' => [],
            ];
        }

        return $tree;
    }

    /**
     * Resolve the URL for a single navigation item. Returns "#" when the
     * linked resource no longer exists.
     */
    public function resolveUrl(NavigationItem $item): string
    {
        $menu = $item->menu()->withoutGlobalScopes()->firstOrFail();

        return $this->resolveUrlUsing($item, $this->resourceHandleMaps($menu, [$item])) ?? '#';
    }

    /**
     * Forget the cached tree for a store's menu handle.
     */
    public function forget(int $storeId, string $handle): void
    {
        Cache::forget("navigation_tree:{$storeId}:{$handle}");
    }

    /**
     * Resolve an item URL using prefetched handle maps. Returns null when the
     * linked resource is missing.
     *
     * @param  array<string, array<int, string>>  $handleMaps
     */
    protected function resolveUrlUsing(NavigationItem $item, array $handleMaps): ?string
    {
        if ($item->type === NavigationItemType::Link) {
            return $item->url ?? '/';
        }

        $handle = $handleMaps[$item->type->value][$item->resource_id] ?? null;

        if ($handle === null) {
            return null;
        }

        return match ($item->type) {
            NavigationItemType::Page => "/pages/{$handle}",
            NavigationItemType::Collection => "/collections/{$handle}",
            NavigationItemType::Product => "/products/{$handle}",
            NavigationItemType::Link => $item->url ?? '/',
        };
    }

    /**
     * Bulk-load resource id => handle maps for the given items, scoped to the
     * menu's store.
     *
     * @param  array<int, NavigationItem>  $items
     * @return array<string, array<int, string>>
     */
    protected function resourceHandleMaps(NavigationMenu $menu, array $items): array
    {
        $idsByType = [
            NavigationItemType::Page->value => [],
            NavigationItemType::Collection->value => [],
            NavigationItemType::Product->value => [],
        ];

        foreach ($items as $item) {
            if ($item->type !== NavigationItemType::Link && $item->resource_id !== null) {
                $idsByType[$item->type->value][] = $item->resource_id;
            }
        }

        $maps = [];

        $queries = [
            NavigationItemType::Page->value => Page::query(),
            NavigationItemType::Collection->value => Collection::query(),
            NavigationItemType::Product->value => Product::query(),
        ];

        foreach ($queries as $type => $query) {
            $maps[$type] = $idsByType[$type] === []
                ? []
                : $query->withoutGlobalScopes()
                    ->where('store_id', $menu->store_id)
                    ->whereIn('id', $idsByType[$type])
                    ->pluck('handle', 'id')
                    ->all();
        }

        return $maps;
    }
}
