<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\AnalyticsDaily;
use Livewire\Component;

class Index extends Component
{
    public string $dateRange = 'last_30_days';

    public function render(): mixed
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        $data = [];
        if ($store) {
            $startDate = match ($this->dateRange) {
                'last_7_days' => now()->subDays(6)->format('Y-m-d'),
                'last_90_days' => now()->subDays(89)->format('Y-m-d'),
                default => now()->subDays(29)->format('Y-m-d'),
            };

            $data = AnalyticsDaily::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('date', '>=', $startDate)
                ->where('date', '<=', now()->format('Y-m-d'))
                ->orderBy('date')
                ->get();
        }

        return view('livewire.admin.analytics.index', ['data' => $data])
            ->layout('layouts.admin.app', ['title' => 'Analytics']);
    }
}
