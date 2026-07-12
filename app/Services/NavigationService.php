<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Collection as ProductCollection;
use BackedEnum;
use Illuminate\Support\Facades\Cache;

final class NavigationService
{
    /** @return list<array<string, mixed>> */
    public function buildTree(NavigationMenu $menu): array
    {
        return Cache::remember("navigation:{$menu->store_id}:{$menu->id}", now()->addMinutes(5), fn (): array => $menu->items()
            ->orderBy('position')
            ->get()
            ->map(fn (NavigationItem $item): array => [
                'id' => $item->id,
                'label' => $item->label,
                'type' => $this->value($item->type),
                'url' => $this->resolveUrl($item),
            ])->all());
    }

    public function resolveUrl(NavigationItem $item): string
    {
        return match ($this->value($item->type)) {
            'page' => ($page = Page::withoutGlobalScopes()->find($item->resource_id)) ? '/pages/'.$page->handle : '#',
            'collection' => ($collection = ProductCollection::withoutGlobalScopes()->find($item->resource_id)) ? '/collections/'.$collection->handle : '#',
            'product' => ($product = Product::withoutGlobalScopes()->find($item->resource_id)) ? '/products/'.$product->handle : '#',
            default => (string) ($item->url ?: '#'),
        };
    }

    private function value(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
