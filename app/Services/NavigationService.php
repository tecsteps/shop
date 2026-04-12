<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    public const CACHE_TTL = 300;

    /**
     * Returns a flat list of menu items (no nesting for Phase 3).
     *
     * @return array<int, array{id: int, type: string, label: string, url: string, position: int}>
     */
    public function buildTree(NavigationMenu $menu): array
    {
        return Cache::remember(
            "nav:menu:{$menu->id}",
            self::CACHE_TTL,
            fn (): array => $menu->items()
                ->get()
                ->map(fn (NavigationItem $item): array => [
                    'id' => (int) $item->id,
                    'type' => $item->type->value,
                    'label' => (string) $item->label,
                    'url' => $this->resolveUrl($item),
                    'position' => (int) $item->position,
                ])
                ->all(),
        );
    }

    public function resolveUrl(NavigationItem $item): string
    {
        return $item->resolveUrl();
    }

    public function forgetMenu(NavigationMenu $menu): void
    {
        Cache::forget("nav:menu:{$menu->id}");
    }
}
