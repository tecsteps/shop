<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\AnalyticsDaily;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public function updatedDateRange(): void
    {
        if ($this->dateRange !== 'custom') {
            $this->customStartDate = null;
            $this->customEndDate = null;
        }
    }

    #[Computed]
    public function kpis(): array
    {
        [$start, $end] = $this->getDateRange();

        $metrics = AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $this->getStoreId())
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->selectRaw('COALESCE(SUM(revenue_amount), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(orders_count), 0) as orders_count')
            ->selectRaw('COALESCE(SUM(visits_count), 0) as visits_count')
            ->selectRaw('COALESCE(SUM(add_to_cart_count), 0) as add_to_cart_count')
            ->selectRaw('COALESCE(SUM(checkout_started_count), 0) as checkout_started_count')
            ->selectRaw('COALESCE(SUM(checkout_completed_count), 0) as checkout_completed_count')
            ->first();

        $ordersCount = (int) $metrics->orders_count;
        $totalSales = (int) $metrics->total_sales;
        $visitsCount = (int) $metrics->visits_count;
        $checkoutCompletedCount = (int) $metrics->checkout_completed_count;

        $conversionRate = $visitsCount > 0
            ? round(($checkoutCompletedCount / $visitsCount) * 100, 1)
            : 0.0;

        return [
            'totalSales' => $totalSales,
            'ordersCount' => $ordersCount,
            'averageOrderValue' => $ordersCount > 0 ? (int) round($totalSales / $ordersCount) : 0,
            'conversionRate' => $conversionRate,
            'visitsCount' => $visitsCount,
        ];
    }

    #[Computed]
    public function salesChartData(): array
    {
        [$start, $end] = $this->getDateRange();

        $dailyData = AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $this->getStoreId())
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->orderBy('date')
            ->get(['date', 'revenue_amount', 'orders_count']);

        return [
            'labels' => $dailyData->pluck('date')->toArray(),
            'revenue' => $dailyData->pluck('revenue_amount')->toArray(),
            'orders' => $dailyData->pluck('orders_count')->toArray(),
        ];
    }

    public function formatCurrency(int $amountInCents): string
    {
        return '$'.number_format($amountInCents / 100, 2);
    }

    public function render(): mixed
    {
        return view('livewire.admin.analytics.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Analytics']]]);
    }

    protected function getStoreId(): ?int
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store?->id;
    }

    /**
     * @return array{string, string}
     */
    protected function getDateRange(): array
    {
        return match ($this->dateRange) {
            'today' => [Carbon::today()->format('Y-m-d'), Carbon::today()->format('Y-m-d')],
            'last_7_days' => [Carbon::now()->subDays(6)->format('Y-m-d'), Carbon::today()->format('Y-m-d')],
            'last_30_days' => [Carbon::now()->subDays(29)->format('Y-m-d'), Carbon::today()->format('Y-m-d')],
            'custom' => [
                $this->customStartDate ?? Carbon::now()->subDays(29)->format('Y-m-d'),
                $this->customEndDate ?? Carbon::today()->format('Y-m-d'),
            ],
            default => [Carbon::now()->subDays(29)->format('Y-m-d'), Carbon::today()->format('Y-m-d')],
        };
    }
}
