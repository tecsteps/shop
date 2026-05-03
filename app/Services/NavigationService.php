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
     * @return array<int, array<string, mixed>>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        return Cache::remember("navigation_menu:{$menu->store_id}:{$menu->handle}", now()->addMinutes(5), function () use ($menu): array {
            return $menu->items()
                ->orderBy('position')
                ->get()
                ->map(fn (NavigationItem $item): array => [
                    'label' => $item->label,
                    'url' => $this->resolveUrl($item),
                    'type' => $item->type->value,
                    'children' => [],
                ])
                ->all();
        });
    }

    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Collection => $this->collectionUrl($item),
            NavigationItemType::Product => $this->productUrl($item),
            NavigationItemType::Page => $this->pageUrl($item),
            NavigationItemType::Link => $item->url ?? '#',
        };
    }

    private function collectionUrl(NavigationItem $item): string
    {
        $collection = Collection::withoutGlobalScopes()->find($item->resource_id);

        return $collection ? '/collections/'.$collection->handle : '#';
    }

    private function productUrl(NavigationItem $item): string
    {
        $product = Product::withoutGlobalScopes()->find($item->resource_id);

        return $product ? '/products/'.$product->handle : '#';
    }

    private function pageUrl(NavigationItem $item): string
    {
        $page = Page::withoutGlobalScopes()->find($item->resource_id);

        return $page ? '/pages/'.$page->handle : '#';
    }
}
