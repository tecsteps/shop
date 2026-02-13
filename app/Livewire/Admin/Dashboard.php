<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public string $dateRange = '30d';

    public function render(): View
    {
        $query = Order::query();

        if ($this->dateRange === 'today') {
            $query->whereDate('placed_at', today());
        } elseif ($this->dateRange === '7d') {
            $query->where('placed_at', '>=', now()->subDays(7));
        } elseif ($this->dateRange === '30d') {
            $query->where('placed_at', '>=', now()->subDays(30));
        }

        $totalSales = (clone $query)->sum('total_amount');
        $orderCount = (clone $query)->count();
        $averageOrderValue = $orderCount > 0 ? (int) round($totalSales / $orderCount) : 0;

        $recentOrders = Order::query()
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
