<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\AnalyticsDaily;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $range = '30';

    public function render(): View
    {
        $days = AnalyticsDaily::query()->where('date', '>=', now()->subDays((int) $this->range - 1)->toDateString())->orderBy('date')->get();
        $summary = [
            'visits' => (int) $days->sum('visits_count'),
            'orders' => (int) $days->sum('orders_count'),
            'revenue' => (int) $days->sum('revenue_amount'),
            'add_to_cart' => (int) $days->sum('add_to_cart_count'),
            'checkout_started' => (int) $days->sum('checkout_started_count'),
            'checkout_completed' => (int) $days->sum('checkout_completed_count'),
        ];

        return view('livewire.admin.analytics.index', compact('days', 'summary'))->layout('layouts.admin');
    }
}
