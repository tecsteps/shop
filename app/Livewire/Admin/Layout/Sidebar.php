<?php

namespace App\Livewire\Admin\Layout;

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    public function render(): View
    {
        return view('livewire.admin.layout.sidebar', [
            'groups' => $this->navigationGroups(),
        ]);
    }

    /**
     * The sidebar navigation structure (spec 03 section 1.2). Items whose
     * route is not registered yet (later phases) render as disabled entries
     * and become active automatically once the route exists.
     *
     * @return list<array{label: string|null, items: list<array{label: string, icon: string, route: string, active: string, enabled: bool}>}>
     */
    protected function navigationGroups(): array
    {
        $groups = [
            [
                'label' => null,
                'items' => [
                    ['label' => __('Dashboard'), 'icon' => 'chart-bar', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
                ],
            ],
            [
                'label' => __('Products'),
                'items' => [
                    ['label' => __('Products'), 'icon' => 'cube', 'route' => 'admin.products.index', 'active' => 'admin.products.*'],
                    ['label' => __('Collections'), 'icon' => 'rectangle-stack', 'route' => 'admin.collections.index', 'active' => 'admin.collections.*'],
                    ['label' => __('Inventory'), 'icon' => 'archive-box', 'route' => 'admin.inventory.index', 'active' => 'admin.inventory.*'],
                ],
            ],
            [
                'label' => __('Orders'),
                'items' => [
                    ['label' => __('Orders'), 'icon' => 'shopping-bag', 'route' => 'admin.orders.index', 'active' => 'admin.orders.*'],
                ],
            ],
            [
                'label' => __('Customers'),
                'items' => [
                    ['label' => __('Customers'), 'icon' => 'users', 'route' => 'admin.customers.index', 'active' => 'admin.customers.*'],
                ],
            ],
            [
                'label' => __('Discounts'),
                'items' => [
                    ['label' => __('Discounts'), 'icon' => 'tag', 'route' => 'admin.discounts.index', 'active' => 'admin.discounts.*'],
                ],
            ],
            [
                'label' => __('Content'),
                'items' => [
                    ['label' => __('Pages'), 'icon' => 'document-text', 'route' => 'admin.pages.index', 'active' => 'admin.pages.*'],
                    ['label' => __('Navigation'), 'icon' => 'bars-3', 'route' => 'admin.navigation.index', 'active' => 'admin.navigation.*'],
                    ['label' => __('Themes'), 'icon' => 'paint-brush', 'route' => 'admin.themes.index', 'active' => 'admin.themes.*'],
                ],
            ],
            [
                'label' => null,
                'items' => [
                    ['label' => __('Analytics'), 'icon' => 'chart-pie', 'route' => 'admin.analytics.index', 'active' => 'admin.analytics.*'],
                    ['label' => __('Settings'), 'icon' => 'cog-6-tooth', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*'],
                    ['label' => __('Apps'), 'icon' => 'squares-2x2', 'route' => 'admin.apps.index', 'active' => 'admin.apps.*'],
                    ['label' => __('Developers'), 'icon' => 'code-bracket', 'route' => 'admin.developers.index', 'active' => 'admin.developers.*'],
                ],
            ],
        ];

        foreach ($groups as $groupIndex => $group) {
            foreach ($group['items'] as $itemIndex => $item) {
                $groups[$groupIndex]['items'][$itemIndex]['enabled'] = Route::has($item['route']);
            }
        }

        return $groups;
    }
}
