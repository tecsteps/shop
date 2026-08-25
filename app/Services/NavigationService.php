<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        return Cache::remember('nav:'.$menu->store_id.':'.$menu->handle, 300, function () use ($menu) {
            return $menu->items()->orderBy('position')->get()
                ->map(fn (NavigationItem $item) => [
                    'label' => $item->label,
                    'url' => $this->resolveUrl($item),
                    'type' => $item->type,
                ])
                ->all();
        });
    }

    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            'page' => route('storefront.page', ['handle' => \App\Models\Page::find($item->resource_id)?->handle], false),
            'collection' => route('storefront.collection', ['handle' => \App\Models\Collection::find($item->resource_id)?->handle], false),
            'product' => route('storefront.product', ['handle' => \App\Models\Product::find($item->resource_id)?->handle], false),
            default => (string) $item->url,
        };
    }
}
