<?php

namespace App\Livewire\Storefront;

use App\Enums\AnalyticsEventType;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    public ?Collection $featuredCollection = null;

    public LaravelCollection $featuredProducts;

    public function mount(AnalyticsService $analytics): void
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        $this->featuredCollection = Collection::query()
            ->where('handle', 'featured')
            ->first();

        $this->featuredProducts = Product::query()
            ->with(['variants' => fn ($q) => $q->orderBy('position')->orderBy('id'), 'media' => fn ($q) => $q->orderBy('position')])
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

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
