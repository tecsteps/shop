<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Order;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Livewire\Component;

class Dashboard extends Component
{
    use ResolvesAdminStore;

    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public function mount(): void
    {
        abort_if(auth()->user() === null, 403);
    }

    public function updatedDateRange(): void
    {
        $this->customStartDate = null;
        $this->customEndDate = null;
    }

    public function render()
    {
        $store = $this->store();
        $analytics = app(AnalyticsService::class);
        [$from, $to] = $this->resolveRange();

        $daily = $analytics->getDailyMetrics($store, $from, $to);
        $summary = $analytics->summary($store, $from, $to);

        $ordersQuery = Order::query()
            ->where('store_id', $store->getKey())
            ->whereBetween('placed_at', [$from.' 00:00:00', $to.' 23:59:59']);

        $ordersCount = (clone $ordersQuery)->count();
        $totalSales = (int) (clone $ordersQuery)->sum('total_amount');
        $averageOrderValue = $ordersCount > 0 ? intdiv($totalSales, $ordersCount) : 0;
        $visitorsCount = (int) $daily->sum('visits_count');

        [$prevFrom, $prevTo] = $this->previousRange($from, $to);
        $prevDaily = $analytics->getDailyMetrics($store, $prevFrom, $prevTo);
        $prevOrders = Order::query()
            ->where('store_id', $store->getKey())
            ->whereBetween('placed_at', [$prevFrom.' 00:00:00', $prevTo.' 23:59:59']);
        $prevOrdersCount = $prevOrders->count();
        $prevSales = (int) (clone $prevOrders)->sum('total_amount');
        $prevAov = $prevOrdersCount > 0 ? intdiv($prevSales, $prevOrdersCount) : 0;
        $prevVisitors = (int) $prevDaily->sum('visits_count');

        $ordersChartData = Order::query()
            ->where('store_id', $store->getKey())
            ->whereBetween('placed_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row): array => ['date' => $row->date, 'count' => (int) $row->count])
            ->all();

        $recentOrders = Order::query()
            ->where('store_id', $store->getKey())
            ->with('customer')
            ->orderByDesc('placed_at')
            ->limit(10)
            ->get();

        return view('livewire.admin.dashboard', [
            'store' => $store,
            'from' => $from,
            'to' => $to,
            'totalSales' => $totalSales,
            'ordersCount' => $ordersCount,
            'averageOrderValue' => $averageOrderValue,
            'visitorsCount' => $visitorsCount,
            'salesChange' => $this->change($totalSales, $prevSales),
            'ordersChange' => $this->change($ordersCount, $prevOrdersCount),
            'aovChange' => $this->change($averageOrderValue, $prevAov),
            'visitorsChange' => $this->change($visitorsCount, $prevVisitors),
            'ordersChartData' => $ordersChartData,
            'topProducts' => $summary['top_products'],
            'funnelData' => $analytics->funnel($store, $from, $to),
            'recentOrders' => $recentOrders,
        ])->layout('livewire.admin.layout.app', ['title' => 'Dashboard']);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveRange(): array
    {
        $today = CarbonImmutable::today();

        if ($this->dateRange === 'custom' && $this->customStartDate && $this->customEndDate) {
            return [$this->customStartDate, $this->customEndDate];
        }

        return match ($this->dateRange) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'last_7_days' => [$today->subDays(6)->toDateString(), $today->toDateString()],
            default => [$today->subDays(29)->toDateString(), $today->toDateString()],
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function previousRange(string $from, string $to): array
    {
        $days = CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) + 1;

        return [
            CarbonImmutable::parse($from)->subDays($days)->toDateString(),
            CarbonImmutable::parse($from)->subDay()->toDateString(),
        ];
    }

    protected function change(int|float $current, int|float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
