<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
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
    public array $recentOrders = [];

    public function mount(): void
    {
        $this->loadKpis();
        $this->loadRecentOrders();
    }

    public function updatedDateRange(): void
    {
        $this->loadKpis();
    }

    public function updatedCustomStartDate(): void
    {
        if ($this->dateRange === 'custom') {
            $this->loadKpis();
        }
    }

    public function updatedCustomEndDate(): void
    {
        if ($this->dateRange === 'custom') {
            $this->loadKpis();
        }
    }

    public function loadKpis(): void
    {
        $store = app('current_store');
        [$start, $end] = $this->getDateRange();
        $periodDays = $start->diffInDays($end) ?: 1;

        $previousStart = $start->copy()->subDays($periodDays);
        $previousEnd = $start->copy();

        $currentOrders = Order::query()
            ->where('store_id', $store->id)
            ->whereBetween('placed_at', [$start, $end])
            ->whereNotNull('placed_at');

        $this->totalSales = (int) (clone $currentOrders)->sum('total_amount');
        $this->ordersCount = (clone $currentOrders)->count();
        $this->averageOrderValue = $this->ordersCount > 0
            ? (int) round($this->totalSales / $this->ordersCount)
            : 0;

        $previousOrders = Order::query()
            ->where('store_id', $store->id)
            ->whereBetween('placed_at', [$previousStart, $previousEnd])
            ->whereNotNull('placed_at');

        $previousSales = (int) $previousOrders->sum('total_amount');
        $previousCount = $previousOrders->count();
        $previousAov = $previousCount > 0 ? (int) round($previousSales / $previousCount) : 0;

        $this->salesChange = $this->calculateChange($this->totalSales, $previousSales);
        $this->ordersChange = $this->calculateChange($this->ordersCount, $previousCount);
        $this->aovChange = $this->calculateChange($this->averageOrderValue, $previousAov);
    }

    public function loadRecentOrders(): void
    {
        $store = app('current_store');

        $this->recentOrders = Order::query()
            ->where('store_id', $store->id)
            ->whereNotNull('placed_at')
            ->latest('placed_at')
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'email' => $order->email,
                'total_amount' => $order->total_amount,
                'financial_status' => $order->financial_status->value,
                'fulfillment_status' => $order->fulfillment_status->value,
                'placed_at' => $order->placed_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function formattedTotalSales(): string
    {
        return '$'.number_format($this->totalSales / 100, 2);
    }

    public function formattedAov(): string
    {
        return '$'.number_format($this->averageOrderValue / 100, 2);
    }

    /** @return array{Carbon, Carbon} */
    protected function getDateRange(): array
    {
        return match ($this->dateRange) {
            'today' => [Carbon::today(), Carbon::now()],
            'last_7_days' => [Carbon::now()->subDays(7), Carbon::now()],
            'last_30_days' => [Carbon::now()->subDays(30), Carbon::now()],
            'custom' => [
                $this->customStartDate ? Carbon::parse($this->customStartDate)->startOfDay() : Carbon::now()->subDays(30),
                $this->customEndDate ? Carbon::parse($this->customEndDate)->endOfDay() : Carbon::now(),
            ],
            default => [Carbon::now()->subDays(30), Carbon::now()],
        };
    }

    protected function calculateChange(int|float $current, int|float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}
