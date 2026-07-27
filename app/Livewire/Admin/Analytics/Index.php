<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\SearchQuery;
use App\Models\Store;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    /**
     * Active date range preset in days (spec 03 §17 date-range filter).
     */
    public int $dateRange = 30;

    public function mount(): void
    {
        Gate::authorize('view-analytics');
    }

    public function updatedDateRange(): void
    {
        // Re-render re-fetches all analytics data for the selected range.
    }

    public function render(AnalyticsService $analytics): View
    {
        /** @var Store $store */
        $store = app('current_store');

        [$start, $end] = $this->period();

        $metrics = $analytics->getDailyMetrics($store, $start->toDateString(), $end->toDateString());

        $totalSales = (int) $metrics->sum('revenue_amount');
        $ordersCount = (int) $metrics->sum('orders_count');
        $visits = (int) $metrics->sum('visits_count');
        $completed = (int) $metrics->sum('checkout_completed_count');

        [$topProducts, $topProductsRevenue] = $this->topProducts($store, $start, $end);

        return view('livewire.admin.analytics.index', [
            'totalSales' => $totalSales,
            'ordersCount' => $ordersCount,
            'averageOrderValue' => $ordersCount > 0 ? intdiv($totalSales, $ordersCount) : 0,
            'conversionRate' => $visits > 0 ? round($completed / $visits * 100, 1) : null,
            'salesChart' => $this->chartData($metrics, 'revenue_amount'),
            'trafficChart' => $this->chartData($metrics, 'visits_count'),
            'funnel' => $this->funnel($metrics),
            'topProducts' => $topProducts,
            'topProductsRevenue' => $topProductsRevenue,
            'recentSearches' => SearchQuery::query()->latest('created_at')->limit(10)->get(),
            'currency' => $store->default_currency,
        ])->layout('admin.layouts.app')->title('Analytics');
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
     * Daily values for an SVG chart, including polyline points (same
     * pattern as the admin dashboard).
     *
     * @param  Collection<string, array<string, mixed>>  $metrics
     * @return array{days: list<array{date: string, value: int}>, points: string, max: int}
     */
    private function chartData(Collection $metrics, string $key): array
    {
        $days = $metrics->values()
            ->map(fn (array $day): array => ['date' => $day['date'], 'value' => (int) $day[$key]])
            ->all();

        $max = max(1, max(array_column($days, 'value') ?: [0]));
        $count = count($days);

        $points = [];

        foreach ($days as $index => $day) {
            $x = $count > 1 ? $index * (600 / ($count - 1)) : 300;
            $y = 150 - ($day['value'] / $max) * 140;
            $points[] = round($x, 1).','.round($y, 1);
        }

        return [
            'days' => $days,
            'points' => implode(' ', $points),
            'max' => $max,
        ];
    }

    /**
     * Conversion funnel steps with percentage-of-visits and relative bar
     * widths (spec 03 §17 funnel visualization).
     *
     * @param  Collection<string, array<string, mixed>>  $metrics
     * @return list<array{label: string, count: int, percent: float|null, width: float}>
     */
    private function funnel(Collection $metrics): array
    {
        $visits = (int) $metrics->sum('visits_count');

        $steps = [
            ['label' => 'Visits', 'count' => $visits],
            ['label' => 'Added to cart', 'count' => (int) $metrics->sum('add_to_cart_count')],
            ['label' => 'Checkout started', 'count' => (int) $metrics->sum('checkout_started_count')],
            ['label' => 'Checkout completed', 'count' => (int) $metrics->sum('checkout_completed_count')],
        ];

        $max = max(1, max(array_column($steps, 'count')));

        return array_map(fn (array $step): array => [
            'label' => $step['label'],
            'count' => $step['count'],
            'percent' => $visits > 0 ? round($step['count'] / $visits * 100, 1) : null,
            'width' => round($step['count'] / $max * 100, 1),
        ], $steps);
    }

    /**
     * Top products by revenue within the period, aggregated from order
     * lines, plus the total line revenue used for the share column.
     *
     * @return array{0: Collection<int, object>, 1: int}
     */
    private function topProducts(Store $store, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $base = DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $store->id)
            ->whereNotNull('orders.placed_at')
            ->whereBetween('orders.placed_at', [$start, $end]);

        $rows = (clone $base)
            ->groupBy('order_lines.product_id', 'order_lines.title_snapshot')
            ->selectRaw('order_lines.title_snapshot as title')
            ->selectRaw('SUM(order_lines.quantity) as units')
            ->selectRaw('SUM(order_lines.total_amount) as revenue')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $total = (int) ((clone $base)->sum('order_lines.total_amount') ?? 0);

        return [$rows, $total];
    }
}
