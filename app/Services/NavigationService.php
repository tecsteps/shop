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
    /** @return list<array{id: int, label: string, type: string, url: string, resource_id: ?int, position: int, children: array<never>}> */
    public function buildTree(NavigationMenu $menu): array
    {
        return Cache::remember(
            "navigation_menu:{$menu->store_id}:{$menu->id}",
            now()->addMinutes(5),
            fn (): array => $menu->items()
                ->get()
                ->map(fn (NavigationItem $item): array => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'type' => $item->type->value,
                    'url' => $this->resolveUrl($item),
                    'resource_id' => $item->resource_id,
                    'position' => $item->position,
                    'children' => [],
                ])->all(),
        );
    }

    public function resolveUrl(NavigationItem $item): string
    {
        if ($item->type === NavigationItemType::Link) {
            return $item->url ?? '#';
        }

        $storeId = $item->menu()->valueOrFail('store_id');

        return match ($item->type) {
            NavigationItemType::Page => $this->resourceUrl(Page::class, $item->resource_id, $storeId, '/pages/'),
            NavigationItemType::Collection => $this->resourceUrl(Collection::class, $item->resource_id, $storeId, '/collections/'),
            NavigationItemType::Product => $this->resourceUrl(Product::class, $item->resource_id, $storeId, '/products/'),
            NavigationItemType::Link => $item->url ?? '#',
        };
    }

    /** @param class-string<Page|Collection|Product> $model */
    private function resourceUrl(string $model, ?int $resourceId, int $storeId, string $prefix): string
    {
        if ($resourceId === null) {
            return '#';
        }

        $handle = $model::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereKey($resourceId)
            ->value('handle');

        return $handle === null ? '#' : $prefix.$handle;
    }
}
