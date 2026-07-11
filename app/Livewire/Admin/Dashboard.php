<?php

namespace App\Livewire\Admin;

use App\Models\AnalyticsDaily;
use App\Models\Order;
use App\Models\OrderLine;
use Carbon\CarbonImmutable;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Dashboard extends AdminComponent
{
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public int $visitorsCount = 0;

    /** @var list<array{date: string, count: int}> */
    public array $ordersChartData = [];

    /** @var list<array{title: string, units_sold: int, revenue: int}> */
    public array $topProducts = [];

    /** @var array<string, int> */
    public array $funnelData = [];

    public function mount(): void
    {
        $this->authorizeStore();
        $this->loadDashboard();
    }

    public function updatedDateRange(): void
    {
        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        [$start, $end] = $this->dates();
        $store = $this->currentStore();
        $orders = Order::query()->where('store_id', $store->getKey())->whereBetween('placed_at', [$start, $end]);
        $this->totalSales = (int) (clone $orders)->sum('total_amount');
        $this->ordersCount = (clone $orders)->count();
        $this->averageOrderValue = $this->ordersCount > 0 ? intdiv($this->totalSales, $this->ordersCount) : 0;
        $analytics = AnalyticsDaily::query()->where('store_id', $store->getKey())->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        $this->visitorsCount = (int) (clone $analytics)->sum('visits_count');
        $this->funnelData = [
            'visits' => $this->visitorsCount,
            'add_to_cart' => (int) (clone $analytics)->sum('add_to_cart_count'),
            'checkout_started' => (int) (clone $analytics)->sum('checkout_started_count'),
            'checkout_completed' => (int) (clone $analytics)->sum('checkout_completed_count'),
        ];
        $this->ordersChartData = (clone $orders)
            ->selectRaw('date(placed_at) as order_date, count(*) as aggregate')
            ->groupBy('order_date')->orderBy('order_date')->get()
            ->map(fn ($row): array => ['date' => (string) $row->order_date, 'count' => (int) $row->aggregate])->all();
        $orderLineTable = (new OrderLine)->getTable();
        $orderTable = (new Order)->getTable();
        $this->topProducts = OrderLine::query()
            ->join($orderTable, $orderTable.'.id', '=', $orderLineTable.'.order_id')
            ->where($orderTable.'.store_id', $store->getKey())->whereBetween($orderTable.'.placed_at', [$start, $end])
            ->selectRaw($orderLineTable.'.title_snapshot as title, sum('.$orderLineTable.'.quantity) as units_sold, sum('.$orderLineTable.'.total_amount) as revenue')
            ->groupBy($orderLineTable.'.title_snapshot')->orderByDesc('revenue')->limit(5)->get()
            ->map(fn ($row): array => ['title' => (string) $row->title, 'units_sold' => (int) $row->units_sold, 'revenue' => (int) $row->revenue])->all();
    }

    public function formattedTotalSales(): string
    {
        return $this->currency($this->totalSales);
    }

    public function formattedAov(): string
    {
        return $this->currency($this->averageOrderValue);
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function dates(): array
    {
        $end = CarbonImmutable::today()->endOfDay();
        $start = match ($this->dateRange) {
            'today' => CarbonImmutable::today()->startOfDay(),
            'last_7_days' => $end->subDays(6)->startOfDay(),
            'custom' => CarbonImmutable::parse($this->customStartDate ?? $end->toDateString())->startOfDay(),
            default => $end->subDays(29)->startOfDay(),
        };
        $end = $this->dateRange === 'custom' ? CarbonImmutable::parse($this->customEndDate ?? $end->toDateString())->endOfDay() : $end;

        return [$start, $end];
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
