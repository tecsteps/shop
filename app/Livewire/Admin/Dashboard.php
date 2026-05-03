<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Order;
use App\Models\OrderLine;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    use UsesAdminStore;

    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public function render(): View
    {
        [$start, $end] = $this->dateWindow();
        $ordersQuery = Order::query()->whereBetween('placed_at', [$start, $end]);
        $ordersCount = (clone $ordersQuery)->count();
        $totalSales = (int) (clone $ordersQuery)->sum('total_amount');

        return view('livewire.admin.dashboard', [
            'dateRangeOptions' => $this->dateRangeOptions(),
            'totalSales' => $this->money($totalSales),
            'ordersCount' => $ordersCount,
            'averageOrderValue' => $this->money($ordersCount > 0 ? (int) round($totalSales / $ordersCount) : 0),
            'conversionRate' => $ordersCount > 0 ? '100%' : '0%',
            'chartData' => $this->ordersChartData($start, $end),
            'topProducts' => $this->topProducts($start, $end),
            'recentOrders' => Order::query()->with('customer')->latest('placed_at')->limit(10)->get(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Dashboard',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function dateRangeOptions(): array
    {
        return [
            'today' => 'Today',
            'last_7_days' => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'custom' => 'Custom range',
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateWindow(): array
    {
        $end = now()->toImmutable()->endOfDay();

        return match ($this->dateRange) {
            'today' => [$end->startOfDay(), $end],
            'last_7_days' => [$end->subDays(6)->startOfDay(), $end],
            'custom' => [
                CarbonImmutable::parse($this->customStartDate ?: $end->subDays(29)->toDateString())->startOfDay(),
                CarbonImmutable::parse($this->customEndDate ?: $end->toDateString())->endOfDay(),
            ],
            default => [$end->subDays(29)->startOfDay(), $end],
        };
    }

    /**
     * @return array<int, array{date: string, count: int}>
     */
    private function ordersChartData(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return Order::query()
            ->selectRaw('date(placed_at) as order_date, count(*) as aggregate')
            ->whereBetween('placed_at', [$start, $end])
            ->groupBy(DB::raw('date(placed_at)'))
            ->orderBy('order_date')
            ->get()
            ->map(fn (Order $order): array => [
                'date' => (string) $order->order_date,
                'count' => (int) $order->aggregate,
            ])
            ->all();
    }

    /**
     * @return Collection<int, OrderLine>
     */
    private function topProducts(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return OrderLine::query()
            ->select('order_lines.title_snapshot')
            ->selectRaw('sum(order_lines.quantity) as units_sold, sum(order_lines.total_amount) as revenue')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $this->currentStore()->id)
            ->whereBetween('orders.placed_at', [$start, $end])
            ->groupBy('order_lines.title_snapshot')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
    }
}
