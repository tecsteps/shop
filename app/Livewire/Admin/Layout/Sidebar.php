<?php

namespace App\Livewire\Admin\Layout;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    /**
     * The current route name, used for active-state highlighting.
     */
    public string $currentRoute = '';

    public function mount(): void
    {
        $this->currentRoute = Route::currentRouteName() ?? '';
    }

    /**
     * Navigation structure (spec 03 §1.2). Each item carries the gate or
     * policy ability that decides whether it is shown for the user's role,
     * plus route-name patterns used for active highlighting.
     *
     * @return list<array{group: string|null, items: list<array{label: string, icon: string, route: string, patterns: list<string>, allowed: bool}>}>
     */
    public function navigation(): array
    {
        return [
            ['group' => null, 'items' => [
                ['label' => 'Dashboard', 'icon' => 'chart-bar', 'route' => 'admin.dashboard', 'patterns' => ['admin.dashboard'], 'allowed' => true],
            ]],
            ['group' => 'Products', 'items' => [
                ['label' => 'Products', 'icon' => 'cube', 'route' => 'admin.products.index', 'patterns' => ['admin.products.*'], 'allowed' => Gate::allows('viewAny', Product::class)],
                ['label' => 'Collections', 'icon' => 'rectangle-stack', 'route' => 'admin.collections.index', 'patterns' => ['admin.collections.*'], 'allowed' => Gate::allows('viewAny', Collection::class)],
                ['label' => 'Inventory', 'icon' => 'archive-box', 'route' => 'admin.inventory.index', 'patterns' => ['admin.inventory.*'], 'allowed' => Gate::allows('viewAny', Product::class)],
            ]],
            ['group' => 'Orders', 'items' => [
                ['label' => 'Orders', 'icon' => 'shopping-bag', 'route' => 'admin.orders.index', 'patterns' => ['admin.orders.*'], 'allowed' => Gate::allows('viewAny', Order::class)],
            ]],
            ['group' => 'Customers', 'items' => [
                ['label' => 'Customers', 'icon' => 'users', 'route' => 'admin.customers.index', 'patterns' => ['admin.customers.*'], 'allowed' => Gate::allows('viewAny', Customer::class)],
            ]],
            ['group' => 'Discounts', 'items' => [
                ['label' => 'Discounts', 'icon' => 'tag', 'route' => 'admin.discounts.index', 'patterns' => ['admin.discounts.*'], 'allowed' => Gate::allows('create', Discount::class)],
            ]],
            ['group' => 'Content', 'items' => [
                ['label' => 'Pages', 'icon' => 'document-text', 'route' => 'admin.pages.index', 'patterns' => ['admin.pages.*'], 'allowed' => Gate::allows('create', Page::class)],
                ['label' => 'Navigation', 'icon' => 'bars-3', 'route' => 'admin.navigation.index', 'patterns' => ['admin.navigation.*'], 'allowed' => Gate::allows('manage-navigation')],
                ['label' => 'Themes', 'icon' => 'paint-brush', 'route' => 'admin.themes.index', 'patterns' => ['admin.themes.*'], 'allowed' => Gate::allows('create', Theme::class)],
            ]],
            ['group' => null, 'items' => [
                ['label' => 'Analytics', 'icon' => 'chart-pie', 'route' => 'admin.analytics.index', 'patterns' => ['admin.analytics.*'], 'allowed' => Gate::allows('view-analytics')],
            ]],
            ['group' => 'Settings', 'items' => [
                ['label' => 'Settings', 'icon' => 'cog-6-tooth', 'route' => 'admin.settings.index', 'patterns' => ['admin.settings.*'], 'allowed' => Gate::allows('manage-store-settings')],
                ['label' => 'Shipping', 'icon' => 'truck', 'route' => 'admin.settings.shipping', 'patterns' => ['admin.settings.shipping*'], 'allowed' => Gate::allows('manage-shipping')],
                ['label' => 'Taxes', 'icon' => 'receipt-percent', 'route' => 'admin.settings.taxes', 'patterns' => ['admin.settings.taxes*'], 'allowed' => Gate::allows('manage-taxes')],
                ['label' => 'Search', 'icon' => 'magnifying-glass', 'route' => 'admin.search.settings', 'patterns' => ['admin.search.*'], 'allowed' => Gate::allows('manage-search-settings')],
                ['label' => 'Apps', 'icon' => 'squares-2x2', 'route' => 'admin.apps.index', 'patterns' => ['admin.apps.*'], 'allowed' => Gate::allows('manage-apps')],
                ['label' => 'Developers', 'icon' => 'code-bracket', 'route' => 'admin.developers.index', 'patterns' => ['admin.developers.*'], 'allowed' => Gate::allows('manage-developers')],
            ]],
        ];
    }

    /**
     * Resolve the href for a nav item, falling back to "#" for sections
     * whose routes land in a later phase.
     */
    public function hrefFor(string $routeName): string
    {
        return Route::has($routeName) ? route($routeName) : '#';
    }

    /**
     * Whether the nav item matches the current route.
     *
     * @param  list<string>  $patterns
     */
    public function isActive(array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->currentRoute === $pattern || fnmatch($pattern, $this->currentRoute)) {
                return true;
            }
        }

        return false;
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        return view('livewire.admin.layout.sidebar', [
            'store' => $store,
            'navigation' => array_filter(
                array_map(
                    fn (array $section): array => array_merge($section, [
                        'items' => array_values(array_filter($section['items'], fn (array $item): bool => $item['allowed'])),
                    ]),
                    $this->navigation(),
                ),
                fn (array $section): bool => $section['items'] !== [],
            ),
        ]);
    }
}
