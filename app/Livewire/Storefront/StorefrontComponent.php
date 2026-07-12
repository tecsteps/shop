<?php

namespace App\Livewire\Storefront;

use App\Models\NavigationMenu;
use App\Models\Store;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

abstract class StorefrontComponent extends Component
{
    protected function currentStore(): Store
    {
        abort_unless(app()->bound('current_store'), 404, 'Store not found.');

        /** @var Store $store */
        $store = app('current_store');

        return $store;
    }

    protected function customer(): mixed
    {
        return Auth::guard('customer')->user();
    }

    protected function storefront(View $view, string $title = '', string $metaDescription = ''): View
    {
        $layoutData = $this->layoutData();
        $view->with($layoutData);

        return $view->layout('storefront.layouts.app', [
            'title' => $title === '' ? $this->currentStore()->name : $title,
            'metaDescription' => $metaDescription,
            ...$layoutData,
        ]);
    }

    /** @return array<string, mixed> */
    protected function layoutData(): array
    {
        $store = $this->currentStore();
        $settings = $this->themeSettings();

        $menus = NavigationMenu::query()
            ->where('store_id', $store->getKey())
            ->whereIn('handle', ['main-menu', 'footer-menu'])
            ->with(['items' => fn ($query) => $query->orderBy('position')])
            ->get()
            ->keyBy('handle');

        return [
            'currentStore' => $store,
            'themeSettings' => $settings,
            'mainNavigation' => $this->navigationItems($menus->get('main-menu')),
            'footerNavigation' => $this->navigationItems($menus->get('footer-menu')),
            'storefrontCustomer' => $this->customer(),
        ];
    }

    /** @return array<string, mixed> */
    protected function themeSettings(): array
    {
        $raw = app(ThemeSettingsService::class)->forStore($this->currentStore());
        if (isset($raw['primary_color'])) {
            data_set($raw, 'colors.primary', $raw['primary_color']);
        }
        if (isset($raw['accent_color'])) {
            data_set($raw, 'colors.accent', $raw['accent_color']);
        }
        if (isset($raw['announcement']) && is_string($raw['announcement'])) {
            $raw['announcement'] = [
                'enabled' => $raw['announcement'] !== '',
                'text' => $raw['announcement'],
                'url' => null,
                'background' => '#18181b',
            ];
        }

        return array_replace_recursive([
            'announcement' => ['enabled' => false, 'text' => '', 'url' => null, 'background' => '#18181b'],
            'header' => ['sticky' => true, 'logo_url' => null],
            'colors' => ['primary' => '#1d4ed8', 'secondary' => '#334155', 'accent' => '#f59e0b'],
            'dark_mode' => 'system',
            'home' => [
                'sections' => ['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text'],
                'hero' => [
                    'enabled' => true,
                    'heading' => 'Everyday pieces, thoughtfully selected.',
                    'subheading' => 'Discover quality essentials made for the way you live.',
                    'cta_label' => 'Shop the collection',
                    'cta_url' => '/collections',
                    'image_url' => null,
                ],
            ],
            'footer' => ['description' => null, 'social' => []],
        ], $raw);
    }

    /** @return array<int, array<string, mixed>> */
    private function navigationItems(mixed $menu): array
    {
        if (! $menu) {
            return [];
        }

        return $menu->items->map(function ($item): array {
            $type = $item->type instanceof \BackedEnum ? $item->type->value : $item->type;

            return [
                'label' => $item->label,
                'url' => match ($type) {
                    'page' => $this->resourceUrl(\App\Models\Page::class, $item->resource_id, '/pages/'),
                    'collection' => $this->resourceUrl(\App\Models\Collection::class, $item->resource_id, '/collections/'),
                    'product' => $this->resourceUrl(\App\Models\Product::class, $item->resource_id, '/products/'),
                    default => $item->url ?: '#',
                },
                'children' => Arr::wrap(data_get($item, 'children', [])),
            ];
        })->all();
    }

    private function resourceUrl(string $model, mixed $id, string $prefix): string
    {
        $handle = $model::query()->whereKey($id)->value('handle');

        return $handle ? url($prefix.$handle) : '#';
    }
}
