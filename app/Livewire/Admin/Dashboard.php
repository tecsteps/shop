<?php

namespace App\Livewire\Admin;

use App\Enums\FinancialStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public function render(): View
    {
        $today = now()->startOfDay();

        $ordersToday = Order::query()
            ->whereNotNull('placed_at')
            ->where('placed_at', '>=', $today)
            ->get();

        $revenueToday = (int) $ordersToday
            ->whereIn('financial_status', [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded])
            ->sum('total_amount');

        $ordersCount = $ordersToday->count();
        $aov = $ordersCount > 0 ? (int) round($revenueToday / $ordersCount) : 0;

        $visitsToday = Schema::hasTable('analytics_events')
            ? (int) \DB::table('analytics_events')
                ->where('created_at', '>=', $today)
                ->count()
            : 0;

        $recentOrders = Order::query()
            ->orderByDesc('placed_at')
            ->limit(10)
            ->get();

        return view('livewire.admin.dashboard', [
            'revenueToday' => $revenueToday,
            'ordersCount' => $ordersCount,
            'aov' => $aov,
            'visitsToday' => $visitsToday,
            'recentOrders' => $recentOrders,
        ]);
    }
}
