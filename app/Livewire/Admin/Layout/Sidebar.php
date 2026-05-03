<?php

namespace App\Livewire\Admin\Layout;

use Illuminate\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    public bool $collapsed = false;

    public function toggle(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    public function render(): View
    {
        return view('livewire.admin.layout.sidebar', [
            'groups' => $this->groups(),
        ]);
    }

    /**
     * @return array<int, array{label: string|null, items: array<int, array{label: string, route: string, icon: string}>}>
     */
    private function groups(): array
    {
        return [
            ['label' => null, 'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'chart-bar'],
            ]],
            ['label' => 'Products', 'items' => [
                ['label' => 'Products', 'route' => 'admin.products.index', 'icon' => 'cube'],
                ['label' => 'Collections', 'route' => 'admin.collections.index', 'icon' => 'rectangle-stack'],
                ['label' => 'Inventory', 'route' => 'admin.inventory.index', 'icon' => 'archive-box'],
            ]],
            ['label' => 'Orders', 'items' => [
                ['label' => 'Orders', 'route' => 'admin.orders.index', 'icon' => 'shopping-bag'],
            ]],
            ['label' => 'Customers', 'items' => [
                ['label' => 'Customers', 'route' => 'admin.customers.index', 'icon' => 'users'],
            ]],
            ['label' => 'Discounts', 'items' => [
                ['label' => 'Discounts', 'route' => 'admin.discounts.index', 'icon' => 'tag'],
            ]],
            ['label' => 'Content', 'items' => [
                ['label' => 'Pages', 'route' => 'admin.pages.index', 'icon' => 'document-text'],
                ['label' => 'Navigation', 'route' => 'admin.navigation.index', 'icon' => 'bars-3'],
                ['label' => 'Themes', 'route' => 'admin.themes.index', 'icon' => 'paint-brush'],
            ]],
            ['label' => null, 'items' => [
                ['label' => 'Analytics', 'route' => 'admin.analytics.index', 'icon' => 'chart-pie'],
                ['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'cog-6-tooth'],
                ['label' => 'Apps', 'route' => 'admin.apps.index', 'icon' => 'squares-2x2'],
                ['label' => 'Developers', 'route' => 'admin.developers.index', 'icon' => 'code-bracket'],
            ]],
        ];
    }
}
