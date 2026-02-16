<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Dashboard'])]
class Dashboard extends Component
{
    public string $dateRange = '30';

    public function render(): mixed
    {
        $store = app('current_store');
        $days = $this->dateRange === 'custom' ? 30 : (int) $this->dateRange;
        $since = Carbon::now()->subDays($days);

        $orders = Order::query()
            ->where('store_id', $store->id)
            ->where('placed_at', '>=', $since)
            ->get();

        $totalSales = $orders->sum('total');
        $orderCount = $orders->count();
        $averageOrderValue = $orderCount > 0 ? (int) ($totalSales / $orderCount) : 0;

        $recentOrders = Order::query()
            ->where('store_id', $store->id)
            ->with('customer')
            ->latest('placed_at')
            ->limit(10)
            ->get();

        return view('livewire.admin.dashboard', [
            'totalSales' => $totalSales,
            'orderCount' => $orderCount,
            'averageOrderValue' => $averageOrderValue,
            'recentOrders' => $recentOrders,
        ]);
    }
}
