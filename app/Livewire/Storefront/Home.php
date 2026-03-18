<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ThemeSettingsService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Home')]
class Home extends Component
{
    #[Computed]
    public function featuredCollections(): \Illuminate\Database\Eloquent\Collection
    {
        $settings = app(ThemeSettingsService::class)->getSettings();
        $count = $settings['featured_collections_count'] ?? 4;

        return Collection::query()
            ->where('status', CollectionStatus::Active)
            ->limit($count)
            ->get();
    }

    #[Computed]
    public function featuredProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $settings = app(ThemeSettingsService::class)->getSettings();
        $count = $settings['featured_products_count'] ?? 8;

        return Product::query()
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->with(['variants' => fn ($q) => $q->where('is_default', true), 'media'])
            ->latest('published_at')
            ->limit($count)
            ->get();
    }

    public function render(): View
    {
        $settings = app(ThemeSettingsService::class)->getSettings();

        return view('livewire.storefront.home', [
            'themeSettings' => $settings,
        ])->layout('storefront.layouts.app', ['title' => 'Home']);
    }
}
