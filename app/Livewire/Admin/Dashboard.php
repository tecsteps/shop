<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public string $period = '30d';

    /**
     * @return array{total_sales: int, orders_count: int, aov: int, conversion_rate: float|null}
     */
    #[Computed]
    public function kpis(): array
    {
        $since = $this->periodStart();

        $query = Order::query()->where('placed_at', '>=', $since);

        $totalSales = (int) $query->clone()->sum('total_amount');
        $ordersCount = (int) $query->clone()->count();
        $aov = $ordersCount > 0 ? (int) round($totalSales / $ordersCount) : 0;

        return [
            'total_sales' => $totalSales,
            'orders_count' => $ordersCount,
            'aov' => $aov,
            'conversion_rate' => null,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Order>
     */
    #[Computed]
    public function recentOrders(): \Illuminate\Support\Collection
    {
        return Order::query()
            ->with('customer')
            ->latest('placed_at')
            ->limit(10)
            ->get();
    }

    protected function periodStart(): DateTimeInterface
    {
        return match ($this->period) {
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}
