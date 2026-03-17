<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Dashboard extends Component
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

    /** @var array<int, array{date: string, count: int}> */
    public array $ordersChartData = [];

    /** @var array<int, array{title: string, units_sold: int, revenue: int}> */
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

    public function loadKpis(): void
    {
        [$start, $end] = $this->getDateRange();
        $periodLength = $start->diffInDays($end) ?: 1;

        $previousStart = $start->copy()->subDays($periodLength);
        $previousEnd = $start->copy()->subSecond();

        $currentOrders = Order::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->whereBetween('placed_at', [$start, $end])
            ->whereNotNull('placed_at');

        $previousOrders = Order::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->whereBetween('placed_at', [$previousStart, $previousEnd])
            ->whereNotNull('placed_at');

        $this->totalSales = (int) (clone $currentOrders)->sum('total_amount');
        $this->ordersCount = (clone $currentOrders)->count();
        $this->averageOrderValue = $this->ordersCount > 0
            ? (int) round($this->totalSales / $this->ordersCount)
            : 0;

        $previousSales = (int) (clone $previousOrders)->sum('total_amount');
        $previousCount = (clone $previousOrders)->count();
        $previousAov = $previousCount > 0 ? (int) round($previousSales / $previousCount) : 0;

        $this->salesChange = $previousSales > 0
            ? round(($this->totalSales - $previousSales) / $previousSales * 100, 1)
            : 0;
        $this->ordersChange = $previousCount > 0
            ? round(($this->ordersCount - $previousCount) / $previousCount * 100, 1)
            : 0;
        $this->aovChange = $previousAov > 0
            ? round(($this->averageOrderValue - $previousAov) / $previousAov * 100, 1)
            : 0;
    }

    public function loadChart(): void
    {
        [$start, $end] = $this->getDateRange();

        $orders = Order::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->whereBetween('placed_at', [$start, $end])
            ->whereNotNull('placed_at')
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $this->ordersChartData = [];
        $current = $start->copy()->startOfDay();
        while ($current <= $end) {
            $dateKey = $current->format('Y-m-d');
            $this->ordersChartData[] = [
                'date' => $dateKey,
                'count' => (int) ($orders[$dateKey]->count ?? 0),
            ];
            $current = $current->addDay();
        }
    }

    public function loadTopProducts(): void
    {
        [$start, $end] = $this->getDateRange();

        $this->topProducts = Product::withoutGlobalScopes()
            ->where('products.store_id', session('store_id'))
            ->join('order_lines', 'products.id', '=', 'order_lines.product_id')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->whereBetween('orders.placed_at', [$start, $end])
            ->selectRaw('products.title, SUM(order_lines.quantity) as units_sold, SUM(order_lines.total_amount) as revenue')
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'title' => $p->title,
                'units_sold' => (int) $p->units_sold,
                'revenue' => (int) $p->revenue,
            ])
            ->toArray();
    }

    public function loadFunnel(): void
    {
        // Funnel data from analytics events if available, otherwise zeroes
        $this->funnelData = [
            'visits' => $this->visitorsCount,
            'add_to_cart' => 0,
            'checkout_started' => 0,
            'checkout_completed' => $this->ordersCount,
        ];
    }

    public function getFormattedTotalSalesProperty(): string
    {
        return number_format($this->totalSales / 100, 2);
    }

    public function getFormattedAovProperty(): string
    {
        return number_format($this->averageOrderValue / 100, 2);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function getDateRange(): array
    {
        return match ($this->dateRange) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'last_7_days' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'last_30_days' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            'custom' => [
                $this->customStartDate ? Carbon::parse($this->customStartDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                $this->customEndDate ? Carbon::parse($this->customEndDate)->endOfDay() : now()->endOfDay(),
            ],
            default => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
        };
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.dashboard');
    }
}
