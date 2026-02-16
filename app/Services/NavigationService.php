<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * @return array<int, array{id: int, title: string, url: string, children: array}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $storeId = $menu->store_id;

        return Cache::remember(
            "nav_tree_{$storeId}_{$menu->id}",
            300,
            function () use ($menu): array {
                $items = $menu->items()
                    ->whereNull('parent_id')
                    ->orderBy('position')
                    ->with('children')
                    ->get();

                return $items->map(fn (NavigationItem $item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'url' => $this->resolveUrl($item),
                    'children' => $item->children->map(fn (NavigationItem $child) => [
                        'id' => $child->id,
                        'title' => $child->title,
                        'url' => $this->resolveUrl($child),
                    ])->all(),
                ])->all();
            }
        );
    }

    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url ?? '#',
            NavigationItemType::Page => '/pages/'.$item->resource_id,
            NavigationItemType::Collection => '/collections/'.$item->resource_id,
            NavigationItemType::Product => '/products/'.$item->resource_id,
        };
    }
}
