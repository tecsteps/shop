<?php

namespace App\Livewire\Admin;

use App\Enums\FinancialStatus;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public string $startDate;

    public string $endDate;

    public function mount(): void
    {
        $this->endDate = now()->format('Y-m-d');
        $this->startDate = now()->subDays(29)->format('Y-m-d');
    }

    public function render()
    {
        $store = app('current_store');

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $ordersQuery = Order::query()
            ->where('store_id', $store->id)
            ->whereBetween('placed_at', [$start, $end])
            ->whereIn('financial_status', [
                FinancialStatus::Paid->value,
                FinancialStatus::PartiallyRefunded->value,
                FinancialStatus::Refunded->value,
            ]);

        $orderCount = (clone $ordersQuery)->count();
        $salesAmount = (int) (clone $ordersQuery)->sum('total_amount');
        $aovAmount = $orderCount > 0 ? intdiv($salesAmount, $orderCount) : 0;

        $conversionRate = $this->computeConversionRate($store->id, $start, $end, $orderCount);

        $recentOrders = Order::query()
            ->where('store_id', $store->id)
            ->orderByDesc('placed_at')
            ->limit(10)
            ->get(['id', 'order_number', 'email', 'total_amount', 'currency', 'financial_status', 'placed_at']);

        return view('livewire.admin.dashboard', [
            'store' => $store,
            'metrics' => [
                'sales_amount' => $salesAmount,
                'order_count' => $orderCount,
                'aov_amount' => $aovAmount,
                'conversion_rate' => $conversionRate,
            ],
            'recentOrders' => $recentOrders,
        ]);
    }

    protected function computeConversionRate(int $storeId, Carbon $start, Carbon $end, int $orderCount): float
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('analytics_daily')) {
            return 0.0;
        }

        $visits = (int) \Illuminate\Support\Facades\DB::table('analytics_daily')
            ->where('store_id', $storeId)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->sum('visits_count');

        if ($visits <= 0) {
            return 0.0;
        }

        return round($orderCount / $visits, 4);
    }
}
