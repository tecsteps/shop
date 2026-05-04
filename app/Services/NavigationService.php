<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\Collection as ProductCollection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NavigationService
{
    /**
     * @return array<int, array{label: string, url: string, type: string, external: bool, children: array<int, mixed>}>
     */
    public function forHandle(Store $store, string $handle): array
    {
        $menu = NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('handle', $handle)
            ->first();

        if ($menu === null) {
            return [];
        }

        return $this->buildTree($menu);
    }

    /**
     * @return array<int, array{label: string, url: string, type: string, external: bool, children: array<int, mixed>}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        return Cache::remember($this->cacheKey($menu), now()->addMinutes(5), function () use ($menu): array {
            $items = $menu->items()
                ->with('menu')
                ->orderBy('parent_id')
                ->orderBy('position')
                ->get();

            $itemsByParent = $items->groupBy(fn (NavigationItem $item): string => $item->parent_id === null ? 'root' : (string) $item->parent_id);

            $build = function (string $parentKey) use (&$build, $itemsByParent): array {
                return $itemsByParent->get($parentKey, collect())
                    ->sortBy('position')
                    ->map(fn (NavigationItem $item): array => $this->node($item, $build((string) $item->getKey())))
                    ->values()
                    ->all();
            };

            return $build('root');
        });
    }

    /**
     * @return array{label: string, url: string, type: string, external: bool, children: array<int, mixed>}
     */
    private function node(NavigationItem $item, array $children): array
    {
        return [
            'label' => $item->label,
            'url' => $this->resolveUrl($item),
            'type' => $item->type->value,
            'external' => $this->isExternal((string) ($item->url ?? '')),
            'children' => $children,
        ];
    }

    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url ?: '#',
            NavigationItemType::Page => $this->pageUrl($item),
            NavigationItemType::Collection => $this->collectionUrl($item),
            NavigationItemType::Product => $this->productUrl($item),
        };
    }

    public function forget(NavigationMenu $menu): void
    {
        Cache::forget($this->cacheKey($menu));
    }

    private function pageUrl(NavigationItem $item): string
    {
        $page = Page::withoutGlobalScopes()
            ->where('store_id', $this->storeIdFor($item))
            ->find($item->resource_id);

        return $page ? route('pages.show', $page->handle, false) : '#';
    }

    private function collectionUrl(NavigationItem $item): string
    {
        $collection = ProductCollection::withoutGlobalScopes()
            ->where('store_id', $this->storeIdFor($item))
            ->find($item->resource_id);

        return $collection ? route('collections.show', $collection->handle, false) : '#';
    }

    private function productUrl(NavigationItem $item): string
    {
        $product = Product::withoutGlobalScopes()
            ->where('store_id', $this->storeIdFor($item))
            ->find($item->resource_id);

        return $product ? route('products.show', $product->handle, false) : '#';
    }

    private function storeIdFor(NavigationItem $item): ?int
    {
        if ($item->relationLoaded('menu') && $item->menu !== null) {
            return (int) $item->menu->store_id;
        }

        return NavigationMenu::withoutGlobalScopes()
            ->whereKey($item->menu_id)
            ->value('store_id');
    }

    private function isExternal(string $url): bool
    {
        return Str::startsWith($url, ['http://', 'https://', 'mailto:', 'tel:']);
    }

    private function cacheKey(NavigationMenu $menu): string
    {
        return "navigation:{$menu->store_id}:{$menu->handle}";
    }
}
