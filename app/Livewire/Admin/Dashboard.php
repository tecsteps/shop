<?php

namespace App\Livewire\Admin;

use App\Models\AnalyticsDaily;
use App\Models\OrderLine;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public string $dateRange = 'last_30_days';

    public string $customStartDate = '';

    public string $customEndDate = '';

    public function updatedDateRange(): void
    {
        // Triggers re-render with new data
    }

    public function render(): mixed
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        $kpis = $this->loadKpis($store);
        $chartData = $this->loadChart($store);
        $topProducts = $this->loadTopProducts($store);
        $funnelData = $this->loadFunnel($store);

        return view('livewire.admin.dashboard', [
            'kpis' => $kpis,
            'chartData' => $chartData,
            'topProducts' => $topProducts,
            'funnelData' => $funnelData,
        ])->layout('layouts.admin.app', [
            'title' => 'Dashboard',
        ]);
    }

    /**
     * @return array{start: string, end: string, prevStart: string, prevEnd: string}
     */
    protected function getDateRange(): array
    {
        $end = now()->format('Y-m-d');

        switch ($this->dateRange) {
            case 'today':
                $start = $end;
                $prevEnd = now()->subDay()->format('Y-m-d');
                $prevStart = $prevEnd;
                break;
            case 'last_7_days':
                $start = now()->subDays(6)->format('Y-m-d');
                $prevEnd = now()->subDays(7)->format('Y-m-d');
                $prevStart = now()->subDays(13)->format('Y-m-d');
                break;
            case 'custom':
                $start = $this->customStartDate ?: now()->subDays(29)->format('Y-m-d');
                $end = $this->customEndDate ?: now()->format('Y-m-d');
                $days = Carbon::parse($start)->diffInDays(Carbon::parse($end));
                $prevEnd = Carbon::parse($start)->subDay()->format('Y-m-d');
                $prevStart = Carbon::parse($prevEnd)->subDays($days)->format('Y-m-d');
                break;
            default: // last_30_days
                $start = now()->subDays(29)->format('Y-m-d');
                $prevEnd = now()->subDays(30)->format('Y-m-d');
                $prevStart = now()->subDays(59)->format('Y-m-d');
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
            'prevStart' => $prevStart ?? $start,
            'prevEnd' => $prevEnd ?? $end,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadKpis(mixed $store): array
    {
        if (! $store) {
            return $this->emptyKpis();
        }

        $range = $this->getDateRange();

        $current = AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('date', '>=', $range['start'])
            ->where('date', '<=', $range['end'])
            ->selectRaw('SUM(revenue_amount) as total_revenue, SUM(orders_count) as total_orders, SUM(visits_count) as total_visits')
            ->first();

        $previous = AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('date', '>=', $range['prevStart'])
            ->where('date', '<=', $range['prevEnd'])
            ->selectRaw('SUM(revenue_amount) as total_revenue, SUM(orders_count) as total_orders, SUM(visits_count) as total_visits')
            ->first();

        $totalSales = (int) ($current->total_revenue ?? 0);
        $ordersCount = (int) ($current->total_orders ?? 0);
        $visitorsCount = (int) ($current->total_visits ?? 0);
        $aov = $ordersCount > 0 ? intdiv($totalSales, $ordersCount) : 0;

        $prevSales = (int) ($previous->total_revenue ?? 0);
        $prevOrders = (int) ($previous->total_orders ?? 0);
        $prevVisitors = (int) ($previous->total_visits ?? 0);

        return [
            'totalSales' => $totalSales,
            'ordersCount' => $ordersCount,
            'aov' => $aov,
            'visitorsCount' => $visitorsCount,
            'salesChange' => $this->percentChange($prevSales, $totalSales),
            'ordersChange' => $this->percentChange($prevOrders, $ordersCount),
            'aovChange' => $this->percentChange(
                $prevOrders > 0 ? intdiv($prevSales, $prevOrders) : 0,
                $aov
            ),
            'visitorsChange' => $this->percentChange($prevVisitors, $visitorsCount),
        ];
    }

    /**
     * @return array<int, array{date: string, count: int}>
     */
    protected function loadChart(mixed $store): array
    {
        if (! $store) {
            return [];
        }

        $range = $this->getDateRange();

        return AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('date', '>=', $range['start'])
            ->where('date', '<=', $range['end'])
            ->orderBy('date')
            ->get(['date', 'orders_count'])
            ->map(fn ($row) => ['date' => $row->date, 'count' => $row->orders_count])
            ->toArray();
    }

    /**
     * @return array<int, array{title: string, units_sold: int, revenue: int}>
     */
    protected function loadTopProducts(mixed $store): array
    {
        if (! $store) {
            return [];
        }

        $range = $this->getDateRange();

        return OrderLine::query()
            ->join('orders', 'order_lines.order_id', '=', 'orders.id')
            ->where('orders.store_id', $store->id)
            ->where('orders.placed_at', '>=', $range['start'].' 00:00:00')
            ->where('orders.placed_at', '<=', $range['end'].' 23:59:59')
            ->selectRaw('order_lines.title_snapshot as title, SUM(order_lines.quantity) as units_sold, SUM(order_lines.total_amount) as revenue')
            ->groupBy('order_lines.title_snapshot')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'title' => $row->title,
                'units_sold' => (int) $row->units_sold,
                'revenue' => (int) $row->revenue,
            ])
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    protected function loadFunnel(mixed $store): array
    {
        if (! $store) {
            return ['visits' => 0, 'add_to_cart' => 0, 'checkout_started' => 0, 'checkout_completed' => 0];
        }

        $range = $this->getDateRange();

        $data = AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('date', '>=', $range['start'])
            ->where('date', '<=', $range['end'])
            ->selectRaw('SUM(visits_count) as visits, SUM(add_to_cart_count) as add_to_cart, SUM(checkout_started_count) as checkout_started, SUM(checkout_completed_count) as checkout_completed')
            ->first();

        return [
            'visits' => (int) ($data->visits ?? 0),
            'add_to_cart' => (int) ($data->add_to_cart ?? 0),
            'checkout_started' => (int) ($data->checkout_started ?? 0),
            'checkout_completed' => (int) ($data->checkout_completed ?? 0),
        ];
    }

    protected function percentChange(int $previous, int $current): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyKpis(): array
    {
        return [
            'totalSales' => 0,
            'ordersCount' => 0,
            'aov' => 0,
            'visitorsCount' => 0,
            'salesChange' => 0.0,
            'ordersChange' => 0.0,
            'aovChange' => 0.0,
            'visitorsChange' => 0.0,
        ];
    }
}
