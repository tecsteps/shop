<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Store;
use Carbon\CarbonInterface;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public float $salesChange = 0;

    public float $ordersChange = 0;

    public float $aovChange = 0;

    /** @var array<int, array{date: string, count: int}> */
    public array $ordersChartData = [];

    /** @var array<int, array{title: string, units_sold: int, revenue: int}> */
    public array $topProducts = [];

    public function mount(): void
    {
        $this->loadKpis();
    }

    public function updatedDateRange(): void
    {
        $this->loadKpis();
    }

    public function loadKpis(): void
    {
        $store = app('current_store');
        [$start, $end] = $this->getDateRange();
        $previousDays = $start->diffInDays($end);
        $previousStart = $start->copy()->subDays($previousDays);
        $previousEnd = $start->copy()->subSecond();

        $currentOrders = $this->ordersQuery($store, $start, $end);
        $previousOrders = $this->ordersQuery($store, $previousStart, $previousEnd);

        $this->totalSales = (int) $currentOrders->sum('total_amount');
        $this->ordersCount = $currentOrders->count();
        $this->averageOrderValue = $this->ordersCount > 0
            ? (int) round($this->totalSales / $this->ordersCount)
            : 0;

        $previousSales = (int) $previousOrders->sum('total_amount');
        $previousCount = $previousOrders->count();
        $previousAov = $previousCount > 0 ? (int) round($previousSales / $previousCount) : 0;

        $this->salesChange = $previousSales > 0 ? round(($this->totalSales - $previousSales) / $previousSales * 100, 1) : 0;
        $this->ordersChange = $previousCount > 0 ? round(($this->ordersCount - $previousCount) / $previousCount * 100, 1) : 0;
        $this->aovChange = $previousAov > 0 ? round(($this->averageOrderValue - $previousAov) / $previousAov * 100, 1) : 0;

        $this->loadChart($store, $start, $end);
        $this->loadTopProducts($store, $start, $end);
    }

    public function formattedTotalSales(): string
    {
        return '$'.number_format($this->totalSales / 100, 2);
    }

    public function formattedAov(): string
    {
        return '$'.number_format($this->averageOrderValue / 100, 2);
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    protected function getDateRange(): array
    {
        return match ($this->dateRange) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'last_7_days' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'last_30_days' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            'custom' => [
                $this->customStartDate ? \Illuminate\Support\Carbon::parse($this->customStartDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                $this->customEndDate ? \Illuminate\Support\Carbon::parse($this->customEndDate)->endOfDay() : now()->endOfDay(),
            ],
            default => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
        };
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Order>
     */
    protected function ordersQuery(Store $store, CarbonInterface $start, CarbonInterface $end): \Illuminate\Database\Eloquent\Builder
    {
        return Order::query()
            ->where('store_id', $store->id)
            ->whereBetween('placed_at', [$start, $end]);
    }

    protected function loadChart(Store $store, CarbonInterface $start, CarbonInterface $end): void
    {
        $days = $start->diffInDays($end);
        $this->ordersChartData = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $this->ordersChartData[] = [
                'date' => $date,
                'count' => Order::query()
                    ->where('store_id', $store->id)
                    ->whereDate('placed_at', $date)
                    ->count(),
            ];
        }
    }

    protected function loadTopProducts(Store $store, CarbonInterface $start, CarbonInterface $end): void
    {
        $this->topProducts = Order::query()
            ->where('store_id', $store->id)
            ->whereBetween('placed_at', [$start, $end])
            ->join('order_lines', 'orders.id', '=', 'order_lines.order_id')
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.dashboard');
    }
}
