<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public function render()
    {
        $store = app('current_store');

        return view('livewire.admin.dashboard', [
            'store' => $store,
            'metrics' => [
                'sales_amount' => 0,
                'order_count' => 0,
                'aov_amount' => 0,
                'conversion_rate' => 0,
            ],
            'recentOrders' => [],
        ]);
    }
}
