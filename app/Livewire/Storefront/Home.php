<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ThemeSettingsService;
use Illuminate\View\View;
use Livewire\Component;

class Home extends Component
{
    public function render(): View
    {
        $store = app('current_store');

        return view('livewire.storefront.home', [
            'store' => $store,
            'settings' => app(ThemeSettingsService::class)->forStore($store),
            'collections' => Collection::query()
                ->where('status', CollectionStatus::Active)
                ->latest()
                ->limit(3)
                ->get(),
            'products' => Product::query()
                ->with('variants', 'media')
                ->where('status', ProductStatus::Active)
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit(6)
                ->get(),
        ])->layout('storefront.layouts.app', [
            'title' => $store->name,
        ]);
    }
}
