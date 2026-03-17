<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public string $channelFilter = 'all';

    public string $deviceFilter = 'all';

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public float $conversionRate = 0;

    /** @var array<int, array{date: string, revenue: int, count: int}> */
    public array $salesChartData = [];

    /** @var array<int, array{rank: int, title: string, units_sold: int, revenue: int, percentage: float}> */
    public array $topProducts = [];

    public bool $isExporting = false;

    public ?string $exportUrl = null;

    public function mount(): void
    {
        $this->loadAnalytics();
    }

    public function updatedDateRange(): void
    {
        $this->loadAnalytics();
    }

    public function updatedChannelFilter(): void
    {
        $this->loadAnalytics();
    }

    public function updatedDeviceFilter(): void
    {
        $this->loadAnalytics();
    }

    public function loadAnalytics(): void
    {
        [$start, $end] = $this->getDateRange();

        $orders = Order::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->whereBetween('placed_at', [$start, $end])
            ->whereNotNull('placed_at');

        $this->totalSales = (int) (clone $orders)->sum('total_amount');
        $this->ordersCount = (clone $orders)->count();
        $this->averageOrderValue = $this->ordersCount > 0
            ? (int) round($this->totalSales / $this->ordersCount)
            : 0;

        // Chart data
        $daily = (clone $orders)
            ->selectRaw('DATE(placed_at) as date, SUM(total_amount) as revenue, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $this->salesChartData = [];
        $current = $start->copy()->startOfDay();
        while ($current <= $end) {
            $key = $current->format('Y-m-d');
            $this->salesChartData[] = [
                'date' => $key,
                'revenue' => (int) ($daily[$key]->revenue ?? 0),
                'count' => (int) ($daily[$key]->count ?? 0),
            ];
            $current = $current->addDay();
        }

        // Top products
        $products = Product::withoutGlobalScopes()
            ->where('products.store_id', session('store_id'))
            ->join('order_lines', 'products.id', '=', 'order_lines.product_id')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->whereBetween('orders.placed_at', [$start, $end])
            ->selectRaw('products.title, SUM(order_lines.quantity) as units_sold, SUM(order_lines.total_amount) as revenue')
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $totalRevenue = max($products->sum('revenue'), 1);
        $this->topProducts = $products->map(fn ($p, $i) => [
            'rank' => $i + 1,
            'title' => $p->title,
            'units_sold' => (int) $p->units_sold,
            'revenue' => (int) $p->revenue,
            'percentage' => round($p->revenue / $totalRevenue * 100, 1),
        ])->toArray();
    }

    public function exportCsv(): void
    {
        $this->isExporting = true;
        $this->dispatch('toast', type: 'info', message: 'Export started. This may take a moment.');
        $this->isExporting = false;
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
        return view('livewire.admin.analytics.index');
    }
}
