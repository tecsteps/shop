<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Services\DashboardMetricsService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public function render(DashboardMetricsService $metrics): View
    {
        $store = app('current_store');
        $today = $metrics->forDay($store);

        $recentOrders = Order::query()
            ->orderByDesc('placed_at')
            ->limit(10)
            ->get();

        return view('livewire.admin.dashboard', [
            'revenueToday' => $today['revenue_amount'],
            'ordersCount' => $today['orders_count'],
            'aov' => $today['aov_amount'],
            'visitsToday' => $today['visits_count'],
            'addToCartToday' => $today['add_to_cart_count'],
            'checkoutStartedToday' => $today['checkout_started_count'],
            'checkoutCompletedToday' => $today['checkout_completed_count'],
            'recentOrders' => $recentOrders,
        ]);
    }
}
