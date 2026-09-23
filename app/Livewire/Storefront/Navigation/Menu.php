<?php

namespace App\Livewire\Storefront\Navigation;

use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Menu extends Component
{
    #[Locked]
    public ?int $storeId = null;

    public string $handle = 'main-menu';

    public string $presentation = 'desktop';

    public function boot(): void
    {
        if (! app()->bound('current_store') && $this->storeId !== null) {
            app()->instance('current_store', Store::query()->findOrFail($this->storeId));
        }
    }

    public function mount(string $handle, string $presentation): void
    {
        abort_unless(in_array($handle, ['main-menu', 'footer-menu'], true), 404);
        abort_unless(in_array($presentation, ['mobile', 'desktop', 'footer'], true), 404);

        $this->handle = $handle;
        $this->presentation = $presentation;
        $this->storeId = $this->currentStore()->id;
    }

    public function render(): mixed
    {
        $store = $this->currentStore();
        $menu = NavigationMenu::query()
            ->where('store_id', $store->id)
            ->where('handle', $this->handle)
            ->with(['items' => fn ($query) => $query->whereNull('parent_id')->with('children')->orderBy('position')])
            ->first();

        if ($menu === null) {
            $items = $this->themeMenu($store);
            $columns = $this->themeFooterColumns($store);
        } else {
            $savedItems = $menu->items->flatMap(fn (NavigationItem $item): array => [$item, ...$item->children->all()]);
            $resources = $this->resolveResources($savedItems, $store);
            $items = $menu->items->map(fn (NavigationItem $item): array => $this->mapItem($item, $resources))->all();
            $columns = $this->footerColumns($menu->title, $items);
        }

        return view('livewire.storefront.navigation.menu', [
            'items' => $items,
            'columns' => $columns,
        ]);
    }

    private function currentStore(): Store
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    /** @return array<int, array<string, mixed>> */
    private function themeMenu(Store $store): array
    {
        $settings = $store->theme_settings;
        $legacyKey = $this->handle === 'main-menu' ? 'main_menu' : 'footer_menu';
        $items = data_get($settings, 'navigation.'.$this->handle, data_get($settings, $legacyKey, []));

        return is_array($items) ? $items : [];
    }

    /** @return array<int, array{label: string, children: array<int, array<string, mixed>>}> */
    private function themeFooterColumns(Store $store): array
    {
        $items = $this->themeMenu($store);

        return array_slice(array_values(array_filter(array_map(function (array $item): ?array {
            $links = data_get($item, 'children', data_get($item, 'links', []));

            if (! is_array($links)) {
                return null;
            }

            return [
                'label' => data_get($item, 'label', data_get($item, 'title', 'Explore')),
                'children' => $links,
            ];
        }, $items))), 0, 3);
    }

    /** @param SupportCollection<int, NavigationItem> $items
     * @return array<string, EloquentCollection<int, Model>>
     */
    private function resolveResources(SupportCollection $items, Store $store): array
    {
        $idsByType = $items->filter(fn (NavigationItem $item): bool => $item->type !== 'link' && $item->resource_id !== null)
            ->groupBy('type')
            ->map(fn (SupportCollection $records): array => $records->pluck('resource_id')->map(fn ($id): int => (int) $id)->all());

        $pageIds = $idsByType->get('page', []);
        $collectionIds = $idsByType->get('collection', []);
        $productIds = $idsByType->get('product', []);

        return [
            'page' => $pageIds === []
                ? new EloquentCollection
                : Page::query()->where('store_id', $store->id)->where('status', 'published')->whereIn('id', $pageIds)->get(['id', 'handle']),
            'collection' => $collectionIds === []
                ? new EloquentCollection
                : Collection::query()->where('store_id', $store->id)->where('status', 'active')->whereIn('id', $collectionIds)->get(['id', 'handle']),
            'product' => $productIds === []
                ? new EloquentCollection
                : Product::query()->where('store_id', $store->id)->where('status', 'active')->whereNotNull('published_at')->whereIn('id', $productIds)->get(['id', 'handle']),
        ];
    }

    /** @param array<string, EloquentCollection<int, Model>> $resources
     * @return array{label: string, url: string, children: array<int, array{label: string, url: string}>}
     */
    private function mapItem(NavigationItem $item, array $resources): array
    {
        $children = $item->children->map(fn (NavigationItem $child): array => [
            'label' => $child->label,
            'url' => $this->itemUrl($child, $resources),
        ])->all();

        return [
            'label' => $item->label,
            'url' => $this->itemUrl($item, $resources),
            'children' => $children,
        ];
    }

    /** @param array<string, EloquentCollection<int, Model>> $resources */
    private function itemUrl(NavigationItem $item, array $resources): string
    {
        if ($item->type === 'link') {
            return $this->isAllowedNavigationUrl((string) $item->url) ? (string) $item->url : '#';
        }

        $resource = ($resources[$item->type] ?? collect())->firstWhere('id', (int) $item->resource_id);

        if (! $resource) {
            return '#';
        }

        return match ($item->type) {
            'page' => route('storefront.page', ['handle' => $resource->handle]),
            'collection' => route('storefront.collection', ['handle' => $resource->handle]),
            'product' => route('storefront.product', ['handle' => $resource->handle]),
            default => '#',
        };
    }

    /** @param array<int, array<string, mixed>> $items
     * @return array<int, array{label: string, children: array<int, array<string, mixed>>}>
     */
    private function footerColumns(string $menuTitle, array $items): array
    {
        $columns = [];
        $ungroupedLinks = [];

        foreach ($items as $item) {
            if ($item['children'] !== []) {
                $columns[] = ['label' => $item['label'], 'children' => $item['children']];
            } else {
                $ungroupedLinks[] = ['label' => $item['label'], 'url' => $item['url']];
            }
        }

        if ($ungroupedLinks !== []) {
            array_unshift($columns, ['label' => $menuTitle, 'children' => $ungroupedLinks]);
        }

        return array_slice($columns, 0, 3);
    }

    private function isAllowedNavigationUrl(string $url): bool
    {
        if ($url === '' || preg_match('/[\x00-\x20\\\\]/', $url) === 1) {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
