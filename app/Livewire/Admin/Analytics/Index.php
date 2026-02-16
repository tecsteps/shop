<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Analytics'])]
class Index extends Component
{
    public string $period = '30';

    public function render(): mixed
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');
        $days = (int) $this->period;
        $since = Carbon::now()->subDays($days);

        $orders = Order::query()
            ->where('store_id', $store->id)
            ->where('placed_at', '>=', $since)
            ->get();

        /** @var int $revenue */
        $revenue = $orders->sum('total');
        $orderCount = $orders->count();
        $averageOrderValue = $orderCount > 0 ? (int) ($revenue / $orderCount) : 0;

        // Simulate visits based on orders (mock data)
        $visits = $orderCount * 12;
        $conversionRate = $visits > 0 ? round(($orderCount / $visits) * 100, 1) : 0;

        return view('livewire.admin.analytics.index', [
            'revenue' => $revenue,
            'orderCount' => $orderCount,
            'averageOrderValue' => $averageOrderValue,
            'visits' => $visits,
            'conversionRate' => $conversionRate,
        ]);
    }
}
