<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\Collection as ProductCollection;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * Build the navigation items for a menu handle (e.g. "main-menu", "footer-menu"),
     * resolved to absolute URLs and cached for 5 minutes per store.
     *
     * @return Collection<int, array{label: string, url: string}>
     */
    public function menu(Store $store, string $handle): Collection
    {
        return Cache::remember(
            "storefront:navigation:{$store->id}:{$handle}",
            now()->addMinutes(5),
            function () use ($store, $handle): Collection {
                $items = $store->navigationMenus()
                    ->where('handle', $handle)
                    ->first()
                    ?->items()
                    ->get() ?? collect();

                return $items->map(fn ($item): array => [
                    'label' => $item->label,
                    'url' => $this->resolveUrl($item),
                ]);
            },
        );
    }

    private function resolveUrl(object $item): string
    {
        return match ($item->type) {
            NavigationItemType::Page => Page::query()->find($item->resource_id)?->handle
                ? route('storefront.pages.show', Page::find($item->resource_id)->handle)
                : '#',
            NavigationItemType::Collection => ProductCollection::query()->find($item->resource_id)?->handle
                ? route('storefront.collections.show', ProductCollection::find($item->resource_id)->handle)
                : '#',
            NavigationItemType::Product => Product::query()->find($item->resource_id)?->handle
                ? route('storefront.products.show', Product::find($item->resource_id)->handle)
                : '#',
            default => $item->url ?? '#',
        };
    }
}
