<?php

namespace App\Livewire\Admin\Layout;

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Component;

class Breadcrumbs extends Component
{
    /**
     * Map of route-name segment to [label, index route name].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const SECTIONS = [
        'products' => ['Products', 'admin.products.index'],
        'inventory' => ['Inventory', 'admin.inventory.index'],
        'orders' => ['Orders', 'admin.orders.index'],
        'collections' => ['Collections', 'admin.collections.index'],
        'customers' => ['Customers', 'admin.customers.index'],
        'discounts' => ['Discounts', 'admin.discounts.index'],
        'pages' => ['Pages', 'admin.pages.index'],
        'navigation' => ['Navigation', 'admin.navigation.index'],
        'themes' => ['Themes', 'admin.themes.index'],
        'analytics' => ['Analytics', 'admin.analytics.index'],
        'settings' => ['Settings', 'admin.settings.index'],
        'apps' => ['Apps', 'admin.apps.index'],
        'developers' => ['Developers', 'admin.developers.index'],
        'search' => ['Search', 'admin.search.settings'],
    ];

    public function render(): View
    {
        return view('livewire.admin.layout.breadcrumbs', [
            'trail' => $this->trail(),
        ]);
    }

    /**
     * Build the breadcrumb trail from the current route name (spec 03 §1.4,
     * §19.5). First item is always "Home" linking to the dashboard; the
     * last item is the current page title without a link.
     *
     * @return list<array{label: string, url: string|null}>
     */
    private function trail(): array
    {
        $routeName = Route::currentRouteName() ?? '';
        $request = request();

        $trail = [
            ['label' => 'Home', 'url' => $routeName === 'admin.dashboard' ? null : route('admin.dashboard')],
        ];

        if ($routeName === 'admin.dashboard') {
            $trail[] = ['label' => 'Dashboard', 'url' => null];

            return $trail;
        }

        $segments = explode('.', str($routeName)->after('admin.')->toString());
        $section = $segments[0] ?? '';
        $action = $segments[1] ?? 'index';

        if (! isset(self::SECTIONS[$section])) {
            return $trail;
        }

        [$label, $indexRoute] = self::SECTIONS[$section];
        $indexUrl = Route::has($indexRoute) ? route($indexRoute) : null;

        if ($action === 'index') {
            $trail[] = ['label' => $label, 'url' => null];

            return $trail;
        }

        $trail[] = ['label' => $label, 'url' => $indexUrl];

        $trail[] = match ($action) {
            'create' => ['label' => 'Create', 'url' => null],
            'edit' => ['label' => $this->currentModelTitle($section) ?? 'Edit', 'url' => null],
            'show' => ['label' => $this->currentModelTitle($section) ?? 'Details', 'url' => null],
            default => ['label' => ucfirst($action), 'url' => null],
        };

        return $trail;
    }

    /**
     * Resolve the title of the route-bound model for edit/show pages, e.g.
     * the product title for "Home > Products > Blue T-Shirt" or the order
     * number for "Home > Orders > #1001".
     */
    private function currentModelTitle(string $section): ?string
    {
        $model = request()->route($section === 'products' ? 'product' : rtrim($section, 's'));

        if (! is_object($model)) {
            return null;
        }

        foreach (['title', 'name', 'order_number', 'code', 'email'] as $attribute) {
            if (isset($model->{$attribute}) && (string) $model->{$attribute} !== '') {
                return (string) $model->{$attribute};
            }
        }

        return null;
    }
}
