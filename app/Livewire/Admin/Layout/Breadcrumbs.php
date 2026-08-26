<?php

namespace App\Livewire\Admin\Layout;

use Illuminate\Support\Str;
use Livewire\Component;

class Breadcrumbs extends Component
{
    /**
     * Build the breadcrumb trail from the current route.
     *
     * @return list<array{label: string, href?: string}>
     */
    public function getItemsProperty(): array
    {
        $home = ['label' => 'Home', 'href' => route('admin.dashboard')];
        $routeName = (string) (request()->route()?->getName() ?? '');

        return match (true) {
            $routeName === 'admin.dashboard' => [$home],
            str_starts_with($routeName, 'admin.products') => $this->trailForCrud($home, 'Products', 'admin.products.index', 'product', 'title'),
            str_starts_with($routeName, 'admin.collections') => $this->trailForCrud($home, 'Collections', 'admin.collections.index', 'collection', 'title'),
            str_starts_with($routeName, 'admin.discounts') => $this->trailForCrud($home, 'Discounts', 'admin.discounts.index', 'discount', 'code'),
            str_starts_with($routeName, 'admin.pages') => $this->trailForCrud($home, 'Pages', 'admin.pages.index', 'page', 'title'),
            $routeName === 'admin.orders.index' => [$home, ['label' => 'Orders']],
            $routeName === 'admin.orders.show' => [
                $home,
                ['label' => 'Orders', 'href' => route('admin.orders.index')],
                ['label' => '#'.(request()->route('order')?->order_number ?? '')],
            ],
            $routeName === 'admin.customers.index' => [$home, ['label' => 'Customers']],
            $routeName === 'admin.customers.show' => [
                $home,
                ['label' => 'Customers', 'href' => route('admin.customers.index')],
                ['label' => (string) (request()->route('customer')?->name ?? '')],
            ],
            $routeName === 'admin.inventory.index' => [$home, ['label' => 'Inventory']],
            $routeName === 'admin.navigation.index' => [$home, ['label' => 'Navigation']],
            $routeName === 'admin.themes.index' => [$home, ['label' => 'Themes']],
            $routeName === 'admin.themes.editor' => [
                $home,
                ['label' => 'Themes', 'href' => route('admin.themes.index')],
                ['label' => (string) (request()->route('theme')?->name ?? '')],
            ],
            $routeName === 'admin.settings.index' => [$home, ['label' => 'Settings']],
            $routeName === 'admin.settings.shipping' => [
                $home,
                ['label' => 'Settings', 'href' => route('admin.settings.index')],
                ['label' => 'Shipping'],
            ],
            $routeName === 'admin.settings.taxes' => [
                $home,
                ['label' => 'Settings', 'href' => route('admin.settings.index')],
                ['label' => 'Taxes'],
            ],
            $routeName === 'admin.apps.index' => [$home, ['label' => 'Apps']],
            $routeName === 'admin.apps.show' => [
                $home,
                ['label' => 'Apps', 'href' => route('admin.apps.index')],
                ['label' => (string) (request()->route('installation')?->app?->name ?? '')],
            ],
            $routeName === 'admin.developers.index' => [$home, ['label' => 'Developers']],
            $routeName === 'admin.analytics.index' => [$home, ['label' => 'Analytics']],
            $routeName === 'admin.search.settings' => [$home, ['label' => 'Search']],
            default => [$home],
        };
    }

    /**
     * Build a Home > Section > [Add / Model] trail for CRUD pages.
     *
     * @param  array{label: string, href?: string}  $home
     * @return list<array{label: string, href?: string}>
     */
    private function trailForCrud(array $home, string $section, string $indexRoute, string $param, string $labelAttribute): array
    {
        $routeName = (string) (request()->route()?->getName() ?? '');
        $sectionItem = ['label' => $section, 'href' => route($indexRoute)];

        if ($routeName === $indexRoute) {
            return [$home, ['label' => $section]];
        }

        if (str_ends_with($routeName, '.create')) {
            return [$home, $sectionItem, ['label' => 'Add '.Str::singular($section)]];
        }

        $model = request()->route($param);

        $label = $model ? (string) ($model->{$labelAttribute} ?? '') : '';

        return [$home, $sectionItem, ['label' => $label !== '' ? $label : $section]];
    }

    public function render()
    {
        return view('livewire.admin.layout.breadcrumbs');
    }
}
