<?php

namespace App\Livewire\Storefront;

use App\Enums\AnalyticsEventType;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    public function mount(AnalyticsService $analytics): void
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if ($store instanceof Store) {
            $analytics->track(
                $store,
                AnalyticsEventType::PageView,
                ['path' => '/'],
                session()->getId(),
            );
        }
    }

    public function render(): View
    {
        return view('livewire.storefront.home');
    }
}
