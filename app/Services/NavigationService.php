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
     * Build a navigation tree from menu items, ordered by position.
     *
     * @return array<int, array{id: int, label: string, url: string, type: string}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $storeId = $menu->store_id;
        $cacheKey = "navigation:{$storeId}:menu:{$menu->id}";

        return Cache::remember($cacheKey, 300, function () use ($menu): array {
            $items = $menu->items()->orderBy('position')->get();

            return $items->map(fn (NavigationItem $item): array => [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $this->resolveUrl($item),
                'type' => $item->type->value,
            ])->all();
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
            NavigationItemType::Collection => "/collections/{$item->resource_id}",
            NavigationItemType::Product => "/products/{$item->resource_id}",
        };
    }

    private function resolvePageUrl(?int $pageId): string
    {
        if (! $pageId) {
            return '#';
        }

        $page = Page::withoutGlobalScopes()->find($pageId);

        if (! $page) {
            return '#';
        }

        return "/pages/{$page->handle}";
    }
}
