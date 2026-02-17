<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * Build a tree of navigation items for the given menu.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        $cacheKey = "navigation.{$menu->store_id}.{$menu->handle}";

        return Cache::remember($cacheKey, 300, function () use ($menu) {
            $items = $menu->items()->orderBy('position')->get();

            return $items->map(function (NavigationItem $item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label,
                    'url' => $this->resolveUrl($item),
                    'type' => $item->type,
                ];
            })->all();
        });
    }

    /**
     * Resolve the URL for a navigation item based on its type.
     */
    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url ?? '#',
            NavigationItemType::Page => '/pages/'.$this->resolveResourceHandle($item, 'pages'),
            NavigationItemType::Collection => '/collections/'.$this->resolveResourceHandle($item, 'collections'),
            NavigationItemType::Product => '/products/'.$this->resolveResourceHandle($item, 'products'),
        };
    }

    /**
     * Resolve a resource handle from the resource_id.
     */
    protected function resolveResourceHandle(NavigationItem $item, string $table): string
    {
        if (! $item->resource_id) {
            return '#';
        }

        $record = \Illuminate\Support\Facades\DB::table($table)
            ->where('id', $item->resource_id)
            ->value('handle');

        return $record ?? '#';
    }
}
