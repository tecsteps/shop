<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * Build a navigation tree for a given menu.
     *
     * @return Collection<int, array{id: int, label: string, url: string|null, children: array<int, mixed>}>
     */
    public function buildTree(NavigationMenu $menu): Collection
    {
        $cacheKey = "navigation_tree:{$menu->store_id}:{$menu->handle}";

        return Cache::remember($cacheKey, 300, function () use ($menu): Collection {
            $items = $menu->items()->orderBy('position')->get();

            return $items->map(fn (NavigationItem $item): array => [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $this->resolveUrl($item),
                'type' => $item->type->value,
            ]);
        });
    }

    /**
     * Resolve the URL for a navigation item based on its type.
     */
    public function resolveUrl(NavigationItem $item): ?string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url,
            NavigationItemType::Page => $item->resource_id ? '/pages/'.$item->resource_id : null,
            NavigationItemType::Collection => $item->resource_id ? '/collections/'.$item->resource_id : null,
            NavigationItemType::Product => $item->resource_id ? '/products/'.$item->resource_id : null,
        };
    }

    /**
     * Clear the navigation cache for a store.
     */
    public function clearCache(int $storeId, string $handle): void
    {
        Cache::forget("navigation_tree:{$storeId}:{$handle}");
    }
}
