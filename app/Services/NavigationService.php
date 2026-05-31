<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Builds renderable navigation trees and resolves item URLs for the storefront.
 *
 * A navigation tree is a nested array of plain item data (label, url, children)
 * so views never trigger lazy queries while rendering the header/footer. Trees
 * are resolved per store + menu handle and cached for five minutes; calls to
 * {@see self::forget()} bust the cache when an admin edits a menu.
 *
 * URL resolution maps each item type to a storefront path:
 *   - link       -> the stored url verbatim
 *   - page       -> /pages/{handle}
 *   - collection -> /collections/{handle}
 *   - product    -> /products/{handle}
 *
 * Resource handles are resolved defensively: the catalog Collection/Product
 * models are built in a parallel phase, so when they are absent (or a resource
 * has been deleted) the item falls back to "#" rather than throwing.
 */
class NavigationService
{
    /**
     * Cache TTL (seconds) for resolved navigation trees.
     */
    private const CACHE_TTL = 300;

    /**
     * Build a nested tree for a menu handle in the current store, or null when
     * the menu does not exist.
     *
     * @return list<array{id: int, label: string, url: string, type: string, children: list<array<string, mixed>>}>|null
     */
    public function tree(string $handle): ?array
    {
        $storeId = $this->currentStoreId();

        if ($storeId === null) {
            return null;
        }

        return Cache::remember(
            self::cacheKey($storeId, $handle),
            self::CACHE_TTL,
            function () use ($handle): ?array {
                $menu = NavigationMenu::query()->where('handle', $handle)->first();

                return $menu === null ? null : $this->buildTree($menu);
            },
        );
    }

    /**
     * Build a nested tree (parent items with their children) from a menu's items.
     *
     * @return list<array{id: int, label: string, url: string, type: string, children: list<array<string, mixed>>}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $items = $menu->items()->orderBy('position')->get();

        return $this->mapLevel($items, null);
    }

    /**
     * Resolve the absolute-ish storefront path for a navigation item.
     */
    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url ?: '#',
            NavigationItemType::Page => $this->pathForResource(Page::class, $item->resource_id, '/pages/'),
            NavigationItemType::Collection => $this->pathForResource('App\\Models\\Collection', $item->resource_id, '/collections/'),
            NavigationItemType::Product => $this->pathForResource('App\\Models\\Product', $item->resource_id, '/products/'),
        };
    }

    /**
     * Forget a cached menu tree (call after an admin edits a menu).
     */
    public function forget(int $storeId, string $handle): void
    {
        Cache::forget(self::cacheKey($storeId, $handle));
    }

    /**
     * Recursively map a flat item collection into a nested tree at one parent
     * level.
     *
     * @param  Collection<int, NavigationItem>  $items
     * @return list<array{id: int, label: string, url: string, type: string, children: list<array<string, mixed>>}>
     */
    private function mapLevel(Collection $items, ?int $parentId): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->map(fn (NavigationItem $item): array => [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $this->resolveUrl($item),
                'type' => $item->type->value,
                'children' => $this->mapLevel($items, $item->id),
            ])
            ->values()
            ->all();
    }

    /**
     * Resolve a storefront path for a resource-backed item by looking up the
     * resource's handle. Returns "#" when the model class or the row is absent.
     */
    private function pathForResource(string $modelClass, ?int $resourceId, string $prefix): string
    {
        if ($resourceId === null || ! class_exists($modelClass)) {
            return '#';
        }

        /** @var \Illuminate\Database\Eloquent\Model $modelClass */
        $handle = $modelClass::query()->whereKey($resourceId)->value('handle');

        return $handle === null ? '#' : $prefix.$handle;
    }

    /**
     * Resolve the current store id from the container, or null when unbound.
     */
    private function currentStoreId(): ?int
    {
        return app()->bound('current_store') ? app('current_store')->id : null;
    }

    /**
     * The cache key for a store's resolved menu tree.
     */
    private static function cacheKey(int $storeId, string $handle): string
    {
        return "navigation:{$storeId}:{$handle}";
    }
}
