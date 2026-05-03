<?php

namespace App\Livewire\Admin\Layout;

use Illuminate\View\View;
use Livewire\Component;

class Breadcrumbs extends Component
{
    public string $title = 'Admin';

    public function render(): View
    {
        return view('livewire.admin.layout.breadcrumbs', [
            'items' => $this->items(),
        ]);
    }

    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    private function items(): array
    {
        $route = request()->route()?->getName() ?? 'admin.dashboard';
        $items = [['label' => 'Home', 'url' => route('admin.dashboard')]];

        $labels = [
            'admin.products' => 'Products',
            'admin.collections' => 'Collections',
            'admin.inventory' => 'Inventory',
            'admin.orders' => 'Orders',
            'admin.customers' => 'Customers',
            'admin.discounts' => 'Discounts',
            'admin.settings' => 'Settings',
            'admin.themes' => 'Themes',
            'admin.pages' => 'Pages',
            'admin.navigation' => 'Navigation',
            'admin.analytics' => 'Analytics',
            'admin.search' => 'Search',
            'admin.apps' => 'Apps',
            'admin.developers' => 'Developers',
        ];

        foreach ($labels as $prefix => $label) {
            if (str_starts_with($route, $prefix)) {
                $items[] = ['label' => $label, 'url' => null];
                break;
            }
        }

        if ($this->title !== 'Admin' && end($items)['label'] !== $this->title) {
            $items[] = ['label' => $this->title, 'url' => null];
        }

        return $items;
    }
}
