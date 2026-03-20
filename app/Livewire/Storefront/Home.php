<?php

namespace App\Livewire\Storefront;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ThemeSettingsService;
use Livewire\Component;

class Home extends Component
{
    public function render(): \Illuminate\View\View
    {
        $themeSettings = app(ThemeSettingsService::class);

        $featuredProducts = Product::query()
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->with(['variants' => fn ($q) => $q->where('is_default', true), 'media'])
            ->orderBy('id')
            ->limit(8)
            ->get();

        $featuredCollections = Collection::query()
            ->limit(4)
            ->get();

        return view('livewire.storefront.home', [
            'settings' => $themeSettings->all(),
            'featuredProducts' => $featuredProducts,
            'featuredCollections' => $featuredCollections,
        ])->layout('layouts.storefront.app', [
            'title' => 'Home',
        ]);
    }
}
