<?php

namespace App\Services;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NavigationService
{
    protected const CACHE_TTL_SECONDS = 300;

    /**
     * Build a flat ordered tree of items for the given menu, with resolved URLs.
     *
     * @return array<int, array{id: int, type: string, label: string, url: string, position: int}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        return Cache::remember(
            $this->cacheKey($menu),
            self::CACHE_TTL_SECONDS,
            fn (): array => $menu->items()
                ->orderBy('position')
                ->get()
                ->map(fn (NavigationItem $item): array => [
                    'id' => $item->id,
                    'type' => $item->type->value,
                    'label' => $item->label,
                    'url' => $this->resolveUrl($item),
                    'position' => $item->position,
                ])
                ->all()
        );
    }

    public function resolveUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Link => $item->url ?? '#',
            NavigationItemType::Page => $this->resolvePageUrl($item->resource_id),
            NavigationItemType::Collection => $this->resolveCollectionUrl($item->resource_id),
            NavigationItemType::Product => $this->resolveProductUrl($item->resource_id),
        };
    }

    public function forgetMenu(NavigationMenu $menu): void
    {
        Cache::forget($this->cacheKey($menu));
    }

    public function forgetStore(int $storeId): void
    {
        NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->get()
            ->each(fn (NavigationMenu $menu): bool => Cache::forget($this->cacheKey($menu)));
    }

    protected function cacheKey(NavigationMenu $menu): string
    {
        return "navigation:{$menu->store_id}:{$menu->id}";
    }

    protected function resolvePageUrl(?int $pageId): string
    {
        if (! $pageId) {
            return '#';
        }

        $page = Page::query()->find($pageId);

        if (! $page) {
            return '#';
        }

        return route('storefront.pages.show', $page->handle);
    }

    protected function resolveCollectionUrl(?int $collectionId): string
    {
        if (! $collectionId) {
            return '#';
        }

        $handle = DB::table('collections')->where('id', $collectionId)->value('handle');

        if (! $handle) {
            return '#';
        }

        return route('storefront.collections.show', $handle);
    }

    protected function resolveProductUrl(?int $productId): string
    {
        if (! $productId) {
            return '#';
        }

        $handle = DB::table('products')->where('id', $productId)->value('handle');

        if (! $handle) {
            return '#';
        }

        return route('storefront.products.show', $handle);
    }
}
