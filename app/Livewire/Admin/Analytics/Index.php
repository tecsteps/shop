<?php

namespace App\Livewire\Admin\Analytics;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Order;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use UsesAdminStore;

    public function render(): View
    {
        $orders = Order::query()->latest('placed_at')->get();
        $paidOrders = $orders->where('financial_status.value', 'paid');

        return view('livewire.admin.analytics.index', [
            'totalSales' => $this->money((int) $orders->sum('total_amount')),
            'paidOrders' => $paidOrders->count(),
            'pendingOrders' => $orders->where('financial_status.value', 'pending')->count(),
            'refundedOrders' => $orders->whereIn('financial_status.value', ['refunded', 'partially_refunded'])->count(),
            'dailyOrders' => $orders
                ->groupBy(fn (Order $order): string => $order->placed_at?->toDateString() ?? 'unknown')
                ->map(fn ($orders, string $date): array => ['date' => $date, 'count' => $orders->count()])
                ->values()
                ->take(14),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Analytics',
        ]);
    }
}
