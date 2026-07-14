<?php

namespace App\Livewire\Admin;

use App\Models\AnalyticsDaily;
use App\Models\Order;
use App\Models\OrderLine;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class Dashboard extends AdminComponent
{
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public int $visitorsCount = 0;

    public float $salesChange = 0;

    public float $ordersChange = 0;

    public float $aovChange = 0;

    public float $visitorsChange = 0;

    /** @var list<array{date: string, count: int}> */
    public array $ordersChartData = [];

    /** @var list<array{title: string, units_sold: int, revenue: int}> */
    public array $topProducts = [];

    /** @var array{visits: int, add_to_cart: int, checkout_started: int, checkout_completed: int} */
    public array $funnelData = ['visits' => 0, 'add_to_cart' => 0, 'checkout_started' => 0, 'checkout_completed' => 0];

    public function mount(): void
    {
        $this->requireRoles(['owner', 'admin', 'staff']);
        $this->loadAll();
    }

    public function updatedDateRange(): void
    {
        $this->loadAll();
    }

    public function updatedCustomStartDate(): void
    {
        if ($this->dateRange === 'custom' && $this->customEndDate) {
            $this->loadAll();
        }
    }

    public function updatedCustomEndDate(): void
    {
        if ($this->dateRange === 'custom' && $this->customStartDate) {
            $this->loadAll();
        }
    }

    public function loadAll(): void
    {
        [$start, $end] = $this->dates();
        $days = max(1, $start->diffInDays($end) + 1);
        $previousEnd = $start->subDay();
        $previousStart = $previousEnd->subDays($days - 1);

        $current = $this->summary($start, $end);
        $previous = $this->summary($previousStart, $previousEnd);
        $this->totalSales = $current['sales'];
        $this->ordersCount = $current['orders'];
        $this->averageOrderValue = $this->ordersCount > 0 ? (int) round($this->totalSales / $this->ordersCount) : 0;
        $this->visitorsCount = $current['visitors'];
        $previousAov = $previous['orders'] > 0 ? (int) round($previous['sales'] / $previous['orders']) : 0;
        $this->salesChange = $this->change($this->totalSales, $previous['sales']);
        $this->ordersChange = $this->change($this->ordersCount, $previous['orders']);
        $this->aovChange = $this->change($this->averageOrderValue, $previousAov);
        $this->visitorsChange = $this->change($this->visitorsCount, $previous['visitors']);
        $this->loadChart($start, $end);
        $this->loadTopProducts($start, $end);
        $this->loadFunnel($start, $end);
    }

    private function loadChart(CarbonImmutable $start, CarbonImmutable $end): void
    {
        $rows = Order::query()->whereBetween('placed_at', [$start->startOfDay(), $end->endOfDay()])
            ->selectRaw('date(placed_at) as day, count(*) as aggregate')->groupBy('day')->pluck('aggregate', 'day');
        $this->ordersChartData = [];
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $this->ordersChartData[] = ['date' => $day->format('M j'), 'count' => (int) ($rows[$day->toDateString()] ?? 0)];
        }
    }

    private function loadTopProducts(CarbonImmutable $start, CarbonImmutable $end): void
    {
        $this->topProducts = OrderLine::query()->whereHas('order', fn ($query) => $query->whereBetween('placed_at', [$start->startOfDay(), $end->endOfDay()]))
            ->select('title_snapshot', DB::raw('sum(quantity) as units_sold'), DB::raw('sum(total_amount) as revenue'))
            ->groupBy('title_snapshot')->orderByDesc('revenue')->limit(5)->get()
            ->map(fn ($row): array => ['title' => $row->title_snapshot, 'units_sold' => (int) $row->units_sold, 'revenue' => (int) $row->revenue])->all();
    }

    private function loadFunnel(CarbonImmutable $start, CarbonImmutable $end): void
    {
        $daily = AnalyticsDaily::query()->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        $this->funnelData = [
            'visits' => (int) (clone $daily)->sum('visits_count'),
            'add_to_cart' => (int) (clone $daily)->sum('add_to_cart_count'),
            'checkout_started' => (int) (clone $daily)->sum('checkout_started_count'),
            'checkout_completed' => (int) (clone $daily)->sum('checkout_completed_count'),
        ];
    }

    /** @return array{sales: int, orders: int, visitors: int} */
    private function summary(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $orders = Order::query()->whereBetween('placed_at', [$start->startOfDay(), $end->endOfDay()])->whereNotIn('status', ['cancelled']);

        return [
            'sales' => (int) (clone $orders)->sum('total_amount'),
            'orders' => (int) (clone $orders)->count(),
            'visitors' => (int) AnalyticsDaily::query()->whereBetween('date', [$start->toDateString(), $end->toDateString()])->sum('visits_count'),
        ];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function dates(): array
    {
        $end = CarbonImmutable::today();

        return match ($this->dateRange) {
            'today' => [$end, $end],
            'last_7_days' => [$end->subDays(6), $end],
            'custom' => [CarbonImmutable::parse($this->customStartDate ?: $end->subDays(29)->toDateString())->startOfDay(), CarbonImmutable::parse($this->customEndDate ?: $end->toDateString())->startOfDay()],
            default => [$end->subDays(29), $end],
        };
    }

    private function change(int $current, int $previous): float
    {
        return $previous === 0 ? ($current > 0 ? 100.0 : 0.0) : round((($current - $previous) / $previous) * 100, 1);
    }

    public function render(): View
    {
        return $this->admin(view('admin.dashboard'), 'Dashboard');
    }
}
