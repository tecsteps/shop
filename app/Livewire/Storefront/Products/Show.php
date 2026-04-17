<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\AnalyticsEventType;
use App\Models\Product;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public ?Product $product = null;

    public function mount(string $handle, AnalyticsService $analytics): void
    {
        $this->handle = $handle;

        $this->product = Product::query()
            ->where('handle', $handle)
            ->first();

        $store = app()->bound('current_store') ? app('current_store') : null;

        if ($this->product !== null && $store instanceof Store) {
            $analytics->track(
                $store,
                AnalyticsEventType::ProductView,
                ['product_id' => $this->product->getKey(), 'handle' => $this->product->handle],
                session()->getId(),
            );
        }
    }

    public function render(): View
    {
        return view('livewire.storefront.products.show');
    }
}
