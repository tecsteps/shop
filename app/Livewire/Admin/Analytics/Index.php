<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Analytics')]
class Index extends Component
{
    public string $dateRange = '30';

    public string $channelFilter = 'all';

    public string $deviceFilter = 'all';

    public function render(): View
    {
        $orders = Order::query()->where('placed_at', '>=', now()->subDays((int) $this->dateRange));
        $ordersCount = (clone $orders)->count();
        $totalSales = (int) (clone $orders)->whereIn('financial_status', ['paid', 'partially_refunded'])->sum('total_amount');

        return view('livewire.admin.analytics.index', ['totalSales' => $totalSales, 'ordersCount' => $ordersCount, 'averageOrderValue' => $ordersCount ? intdiv($totalSales, $ordersCount) : 0, 'conversionRate' => 0.0]);
    }
}
