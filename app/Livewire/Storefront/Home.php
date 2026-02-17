<?php

namespace App\Livewire\Storefront;

use App\Services\ThemeSettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Home extends Component
{
    public function render(): \Illuminate\View\View
    {
        $themeSettings = app(ThemeSettingsService::class);
        $storeName = app()->bound('current_store') ? app('current_store')->name : config('app.name');

        $collections = collect();
        $featuredProducts = collect();

        if (app()->bound('current_store')) {
            $store = app('current_store');

            $collections = \App\Models\Collection::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', 'active')
                ->limit($themeSettings->get('featured_collections_count', 4))
                ->get();

            $featuredProducts = \App\Models\Product::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', 'active')
                ->with(['variants.inventoryItem', 'media'])
                ->limit($themeSettings->get('featured_products_count', 8))
                ->get();
        }

        return view('livewire.storefront.home', [
            'themeSettings' => $themeSettings,
            'storeName' => $storeName,
            'collections' => $collections,
            'featuredProducts' => $featuredProducts,
        ])->title($storeName);
    }
}
