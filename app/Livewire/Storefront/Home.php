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
        $themeSettings = app(ThemeSettingsService::class);
        $sections = $themeSettings->get('home_sections', []);
        $hero = $themeSettings->get('hero', []);

        $featuredCollections = Collection::withoutGlobalScopes()
            ->when(app()->bound('current_store'), function ($query): void {
                $query->where('store_id', app('current_store')->id);
            })
            ->where('status', CollectionStatus::Active)
            ->limit(4)
            ->get();

        $featuredProducts = Product::withoutGlobalScopes()
            ->when(app()->bound('current_store'), function ($query): void {
                $query->where('store_id', app('current_store')->id);
            })
            ->where('status', ProductStatus::Active)
            ->with(['variants.inventoryItem', 'media'])
            ->limit(8)
            ->get();

        return view('livewire.storefront.home', [
            'sections' => $sections,
            'hero' => $hero,
            'featuredCollections' => $featuredCollections,
            'featuredProducts' => $featuredProducts,
            'themeSettings' => $themeSettings,
        ])->layout('storefront.layouts.app', [
            'title' => null,
        ]);
    }
}
