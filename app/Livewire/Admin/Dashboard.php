<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Active date range preset in days (spec 03 §2 date-range filter).
     */
    public int $dateRange = 30;

    public function updatedDateRange(): void
    {
        // Re-render re-fetches all KPI data for the selected range.
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        [$start, $end] = $this->period();
        [$previousStart, $previousEnd] = $this->previousPeriod();

        $current = $this->orderMetrics($start, $end);
        $previous = $this->orderMetrics($previousStart, $previousEnd);

        $totalSales = $current['total_sales'];
        $ordersCount = $current['orders_count'];
        $averageOrderValue = $ordersCount > 0 ? (int) intdiv($totalSales, $ordersCount) : 0;
        $previousAov = $previous['orders_count'] > 0 ? (int) intdiv($previous['total_sales'], $previous['orders_count']) : 0;

        $recentOrders = Order::query()
            ->with('customer')
            ->whereNotNull('placed_at')
            ->latest('placed_at')
            ->limit(10)
            ->get();

        return view('livewire.admin.dashboard', [
            'totalSales' => $totalSales,
            'ordersCount' => $ordersCount,
            'averageOrderValue' => $averageOrderValue,
            'conversionRate' => $this->conversionRate($start, $end, $ordersCount),
            'salesChange' => $this->percentageChange($previous['total_sales'], $totalSales),
            'ordersChange' => $this->percentageChange($previous['orders_count'], $ordersCount),
            'aovChange' => $this->percentageChange($previousAov, $averageOrderValue),
            'chart' => $this->chartData($start, $end),
            'recentOrders' => $recentOrders,
            'currency' => $store->default_currency,
        ])->layout('admin.layouts.app')->title('Dashboard');
    }

    /**
     * Inclusive current period: [today - (range - 1) days, now].
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function period(): array
    {
        $end = CarbonImmutable::now()->endOfDay();
        $start = CarbonImmutable::now()->subDays($this->dateRange - 1)->startOfDay();

        return [$start, $end];
    }

    /**
     * The period immediately before the current one, same length.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function previousPeriod(): array
    {
        [$start] = $this->period();

        return [
            $start->subDays($this->dateRange),
            $start->subDay()->endOfDay(),
        ];
    }

    /**
     * Aggregate sales and order count for a period.
     *
     * @return array{total_sales: int, orders_count: int}
     */
    private function orderMetrics(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $row = Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales, COUNT(*) as orders_count')
            ->first();

        return [
            'total_sales' => (int) $row->total_sales,
            'orders_count' => (int) $row->orders_count,
        ];
    }

    /**
     * Conversion rate: orders per unique analytics session (page_view).
     * Null when no analytics events exist for the period.
     */
    private function conversionRate(CarbonImmutable $start, CarbonImmutable $end, int $ordersCount): ?float
    {
        $sessions = DB::table('analytics_events')
            ->where('store_id', app('current_store')->id)
            ->where('type', 'page_view')
            ->whereNotNull('session_id')
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->count('session_id');

        if ($sessions === 0) {
            return null;
        }

        return round($ordersCount / $sessions * 100, 1);
    }

    /**
     * Percentage change between two values, null when there is no baseline.
     */
    private function percentageChange(int $previous, int $current): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * Daily order counts for the chart, including SVG polyline points.
     *
     * @return array{days: list<array{date: string, count: int}>, points: string, max: int}
     */
    private function chartData(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $counts = Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->selectRaw('DATE(placed_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');

        $days = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $days[] = ['date' => $key, 'count' => (int) ($counts[$key] ?? 0)];
        }

        $max = max(1, max(array_column($days, 'count')));
        $count = count($days);

        $points = [];

        foreach ($days as $index => $day) {
            $x = $count > 1 ? $index * (600 / ($count - 1)) : 300;
            $y = 150 - ($day['count'] / $max) * 140;
            $points[] = round($x, 1).','.round($y, 1);
        }

        return [
            'days' => $days,
            'points' => implode(' ', $points),
            'max' => $max,
        ];
    }
}
