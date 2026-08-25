<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Livewire\Admin\Concerns\FormatsMoney;
use App\Models\AnalyticsDaily;
use App\Models\Order;
use App\Models\OrderLine;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    use DispatchesToasts, FormatsMoney;

    #[Layout('layouts.admin.app')]
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public int $visitorsCount = 0;

    public float $salesChange = 0.0;

    public float $ordersChange = 0.0;

    public float $aovChange = 0.0;

    public float $visitorsChange = 0.0;

    /** @var list<array{date: string, count: int}> */
    public array $ordersChartData = [];

    /** @var list<array{title: string, units_sold: int, revenue: int}> */
    public array $topProducts = [];

    /** @var array{visits: int, add_to_cart: int, checkout_started: int, checkout_completed: int} */
    public array $funnelData = [
        'visits' => 0,
        'add_to_cart' => 0,
        'checkout_started' => 0,
        'checkout_completed' => 0,
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', Order::class);

        $this->loadKpis();
        $this->loadChart();
        $this->loadTopProducts();
        $this->loadFunnel();
    }

    public function updatedDateRange(): void
    {
        $this->loadKpis();
        $this->loadChart();
        $this->loadTopProducts();
        $this->loadFunnel();
    }

    #[Computed]
    public function formattedTotalSales(): string
    {
        return $this->formatMoney($this->totalSales);
    }

    #[Computed]
    public function formattedAov(): string
    {
        return $this->formatMoney($this->averageOrderValue);
    }

    public function loadKpis(): void
    {
        ['start' => $start, 'end' => $end] = $this->range();
        $store = app('current_store');

        $orders = Order::where('store_id', $store->id)
            ->whereBetween('placed_at', [$start, $end])
            ->get();

        $this->ordersCount = $orders->count();
        $this->totalSales = (int) $orders->sum('total_amount');
        $this->averageOrderValue = $this->ordersCount > 0 ? intdiv($this->totalSales, $this->ordersCount) : 0;

        $periodLength = $start->diffInDays($end) + 1;
        $prevEnd = $start->subDay()->endOfDay();
        $prevStart = $prevEnd->subDays($periodLength - 1)->startOfDay();

        $previous = Order::where('store_id', $store->id)
            ->whereBetween('placed_at', [$prevStart, $prevEnd])
            ->get();

        $prevSales = (int) $previous->sum('total_amount');
        $prevCount = $previous->count();
        $prevAov = $prevCount > 0 ? intdiv($prevSales, $prevCount) : 0;

        $this->salesChange = $this->percentageChange($prevSales, $this->totalSales);
        $this->ordersChange = $this->percentageChange($prevCount, $this->ordersCount);
        $this->aovChange = $this->percentageChange($prevAov, $this->averageOrderValue);

        $daily = AnalyticsDaily::where('store_id', $store->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $this->visitorsCount = (int) $daily->sum('visits_count');

        $prevDaily = AnalyticsDaily::where('store_id', $store->id)
            ->whereBetween('date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->get();

        $this->visitorsChange = $this->percentageChange((int) $prevDaily->sum('visits_count'), $this->visitorsCount);
    }

    public function loadChart(): void
    {
        ['start' => $start, 'end' => $end] = $this->range();
        $store = app('current_store');

        $counts = Order::where('store_id', $store->id)
            ->whereBetween('placed_at', [$start, $end])
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $data = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $key = $cursor->toDateString();
            $data[] = ['date' => $key, 'count' => (int) ($counts[$key] ?? 0)];
            $cursor = $cursor->addDay();
        }

        $this->ordersChartData = $data;
    }

    public function loadTopProducts(): void
    {
        ['start' => $start, 'end' => $end] = $this->range();
        $store = app('current_store');

        $this->topProducts = OrderLine::query()
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->join('products', 'products.id', '=', 'order_lines.product_id')
            ->where('orders.store_id', $store->id)
            ->whereBetween('orders.placed_at', [$start, $end])
            ->selectRaw('products.title as title, SUM(order_lines.quantity) as units_sold, SUM(order_lines.total_amount) as revenue')
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'title' => $row->title,
                'units_sold' => (int) $row->units_sold,
                'revenue' => (int) $row->revenue,
            ])
            ->all();
    }

    public function loadFunnel(): void
    {
        ['start' => $start, 'end' => $end] = $this->range();
        $store = app('current_store');

        $daily = AnalyticsDaily::where('store_id', $store->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $this->funnelData = [
            'visits' => (int) $daily->sum('visits_count'),
            'add_to_cart' => (int) $daily->sum('add_to_cart_count'),
            'checkout_started' => (int) $daily->sum('checkout_started_count'),
            'checkout_completed' => (int) $daily->sum('checkout_completed_count'),
        ];
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function range(): array
    {
        if ($this->dateRange === 'custom' && $this->customStartDate && $this->customEndDate) {
            return [
                'start' => CarbonImmutable::parse($this->customStartDate)->startOfDay(),
                'end' => CarbonImmutable::parse($this->customEndDate)->endOfDay(),
            ];
        }

        $end = CarbonImmutable::now()->endOfDay();

        $start = match ($this->dateRange) {
            'today' => CarbonImmutable::today()->startOfDay(),
            'last_7_days' => $end->subDays(6)->startOfDay(),
            default => $end->subDays(29)->startOfDay(),
        };

        return ['start' => $start, 'end' => $end];
    }

    private function percentageChange(int $previous, int $current): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
