<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Services\AnalyticsService;
use App\Support\Storefront\PriceFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Analytics dashboard (spec 03 section 17): KPI tiles, daily sales chart,
 * conversion funnel, top products, and top referrers over a date range.
 * KPIs and the chart read the pre-aggregated analytics_daily table; the
 * funnel and traffic tables read the raw event stream.
 */
#[Layout('layouts::admin')]
class Index extends Component
{
    /**
     * Date range preset: today, last_7_days, last_30_days, or custom.
     */
    #[Url]
    public string $dateRange = 'last_30_days';

    #[Url(except: '')]
    public string $customStartDate = '';

    #[Url(except: '')]
    public string $customEndDate = '';

    public function mount(): void
    {
        $this->authorize('viewAnalytics', $this->store());
    }

    public function render(AnalyticsService $analytics): View
    {
        $this->authorize('viewAnalytics', $this->store());

        [$start, $end] = $this->range();
        $days = (int) $start->diffInDays($end) + 1;
        [$previousStart, $previousEnd] = [$start->subDays($days), $start->subDay()];

        $current = $this->kpisBetween($analytics, $start, $end);
        $previous = $this->kpisBetween($analytics, $previousStart, $previousEnd);
        $funnel = $analytics->eventCountsBetween($this->store(), $start->toDateTimeString(), $end->addDay()->toDateTimeString());

        return view('livewire.admin.analytics.index', [
            'totalSales' => $current['total_sales'],
            'ordersCount' => $current['orders_count'],
            'averageOrderValue' => $current['average_order_value'],
            'conversionRate' => $current['conversion_rate'],
            'salesChange' => $this->percentChange($previous['total_sales'], $current['total_sales']),
            'ordersChange' => $this->percentChange($previous['orders_count'], $current['orders_count']),
            'aovChange' => $this->percentChange($previous['average_order_value'], $current['average_order_value']),
            'conversionChange' => $this->percentChange($previous['conversion_rate'], $current['conversion_rate']),
            'formattedTotalSales' => PriceFormatter::format($current['total_sales'], $this->currency()),
            'formattedAov' => PriceFormatter::format($current['average_order_value'], $this->currency()),
            'chart' => $this->salesChart($analytics, $start, $end),
            'funnel' => $this->funnelSteps($funnel),
            'topProducts' => $this->topProducts($start, $end),
            'topReferrers' => $this->topReferrers($start, $end),
            'visitsCount' => $current['visits_count'],
        ])->title(__('Analytics'));
    }

    /**
     * KPIs from analytics_daily over an inclusive date range.
     *
     * @return array{total_sales: int, orders_count: int, average_order_value: int, visits_count: int, conversion_rate: float}
     */
    protected function kpisBetween(AnalyticsService $analytics, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $analytics->getDailyMetrics($this->store(), $start->toDateString(), $end->toDateString());

        $totalSales = (int) $rows->sum('revenue_amount');
        $ordersCount = (int) $rows->sum('orders_count');
        $visitsCount = (int) $rows->sum('visits_count');

        return [
            'total_sales' => $totalSales,
            'orders_count' => $ordersCount,
            'average_order_value' => $ordersCount > 0 ? intdiv($totalSales, $ordersCount) : 0,
            'visits_count' => $visitsCount,
            'conversion_rate' => $visitsCount > 0 ? round($ordersCount / $visitsCount * 100, 1) : 0.0,
        ];
    }

    /**
     * Daily revenue with precomputed SVG polyline geometry (same
     * dependency-free chart approach as the Dashboard).
     *
     * @return array{days: list<array{date: string, amount: int}>, max: int, points: string, area: string}
     */
    protected function salesChart(AnalyticsService $analytics, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $amountsByDay = $analytics->getDailyMetrics($this->store(), $start->toDateString(), $end->toDateString())
            ->pluck('revenue_amount', 'date');

        $days = [];

        for ($date = $start; $date <= $end; $date = $date->addDay()) {
            $days[] = [
                'date' => $date->toDateString(),
                'amount' => (int) ($amountsByDay[$date->toDateString()] ?? 0),
            ];
        }

        $max = max(1, ...array_column($days, 'amount'));

        $width = 600;
        $height = 180;
        $stepX = count($days) > 1 ? $width / (count($days) - 1) : $width;

        $points = [];

        foreach ($days as $index => $day) {
            $x = round($index * $stepX, 1);
            $y = round($height - ($day['amount'] / $max) * ($height - 10) - 5, 1);
            $points[] = "{$x},{$y}";
        }

        $polyline = implode(' ', $points);

        return [
            'days' => $days,
            'max' => $max,
            'points' => $polyline,
            'area' => "0,{$height} {$polyline} {$width},{$height}",
        ];
    }

    /**
     * The conversion funnel steps with widths proportional to the first
     * step (page_view -> product_view -> add_to_cart -> checkout_started
     * -> checkout_completed).
     *
     * @param  array<string, int>  $counts
     * @return list<array{label: string, count: int, percent: float}>
     */
    protected function funnelSteps(array $counts): array
    {
        $steps = [
            ['label' => __('Page views'), 'count' => $counts['page_view']],
            ['label' => __('Product views'), 'count' => $counts['product_view']],
            ['label' => __('Add to cart'), 'count' => $counts['add_to_cart']],
            ['label' => __('Checkout started'), 'count' => $counts['checkout_started']],
            ['label' => __('Checkout completed'), 'count' => $counts['checkout_completed']],
        ];

        $base = max(1, $steps[0]['count']);

        return array_map(fn (array $step): array => [
            ...$step,
            'percent' => round($step['count'] / $base * 100, 1),
        ], $steps);
    }

    /**
     * Top 10 products by revenue within the range, with share of total.
     *
     * @return list<array{title: string, units_sold: int, revenue: int, share: float}>
     */
    protected function topProducts(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $this->store()->getKey())
            ->where('orders.placed_at', '>=', $start)
            ->where('orders.placed_at', '<', $end->addDay())
            ->groupBy('order_lines.title_snapshot')
            ->selectRaw('order_lines.title_snapshot as title, SUM(order_lines.quantity) as units_sold, SUM(order_lines.total_amount) as revenue')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $totalRevenue = max(1, (int) $rows->sum('revenue'));

        return $rows->map(fn (object $row): array => [
            'title' => $row->title,
            'units_sold' => (int) $row->units_sold,
            'revenue' => (int) $row->revenue,
            'share' => round((int) $row->revenue / $totalRevenue * 100, 1),
        ])->all();
    }

    /**
     * Traffic sources: sessions grouped by the referrer host of their
     * page_view events, with per-source order conversion.
     *
     * @return list<array{source: string, sessions: int, orders: int, conversion: float}>
     */
    protected function topReferrers(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $events = AnalyticsEvent::query()
            ->withoutGlobalScopes()
            ->where('store_id', $this->store()->getKey())
            ->whereIn('type', ['page_view', 'checkout_completed'])
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end->addDay())
            ->get(['type', 'session_id', 'properties_json']);

        $sourceBySession = [];
        $completedSessions = [];

        foreach ($events as $event) {
            if ($event->type === 'checkout_completed') {
                $completedSessions[$event->session_id] = true;

                continue;
            }

            $referrer = $event->properties_json['referrer'] ?? null;
            $source = filled($referrer) ? (parse_url($referrer, PHP_URL_HOST) ?: $referrer) : __('Direct');

            $sourceBySession[$event->session_id] ??= $source;
        }

        $sources = [];

        foreach ($sourceBySession as $sessionId => $source) {
            $sources[$source] ??= ['source' => $source, 'sessions' => 0, 'orders' => 0];
            $sources[$source]['sessions']++;

            if (isset($completedSessions[$sessionId])) {
                $sources[$source]['orders']++;
            }
        }

        usort($sources, fn (array $a, array $b): int => $b['sessions'] <=> $a['sessions']);

        return array_map(fn (array $source): array => [
            ...$source,
            'conversion' => $source['sessions'] > 0 ? round($source['orders'] / $source['sessions'] * 100, 2) : 0.0,
        ], array_slice(array_values($sources), 0, 10));
    }

    /**
     * The inclusive [start, end] date range for the selected preset.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function range(): array
    {
        $today = now()->startOfDay();

        if ($this->dateRange === 'custom' && $this->customStartDate !== '' && $this->customEndDate !== '') {
            $start = CarbonImmutable::parse($this->customStartDate)->startOfDay();
            $end = CarbonImmutable::parse($this->customEndDate)->startOfDay();

            return $start <= $end ? [$start, $end] : [$end, $start];
        }

        return match ($this->dateRange) {
            'today' => [$today, $today],
            'last_7_days' => [$today->subDays(6), $today],
            default => [$today->subDays(29), $today],
        };
    }

    protected function percentChange(int|float $previous, int|float $current): float
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    protected function currency(): string
    {
        return $this->store()->default_currency ?? 'EUR';
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
