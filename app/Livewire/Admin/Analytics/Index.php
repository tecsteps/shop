<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Analytics')]
class Index extends Component
{
    public string $dateRange = 'last_30_days';

    public function render(): \Illuminate\Contracts\View\View
    {
        $store = app('current_store');
        $start = match ($this->dateRange) {
            'today' => now()->startOfDay(),
            'last_7_days' => now()->subDays(7)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };

        $totalSales = Order::where('store_id', $store->id)
            ->where('placed_at', '>=', $start)
            ->sum('total_amount');

        $orderCount = Order::where('store_id', $store->id)
            ->where('placed_at', '>=', $start)
            ->count();

        return view('livewire.admin.analytics.index', [
            'totalSales' => $totalSales,
            'orderCount' => $orderCount,
        ]);
    }
}
