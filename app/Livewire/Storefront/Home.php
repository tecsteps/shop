<?php

namespace App\Livewire\Storefront;

use App\Models\Collection;
use App\Models\Product;
use App\Services\ThemeSettingsService;
use Livewire\Component;

class Home extends Component
{
    /** @var array<string, mixed> */
    public array $themeSettings = [];

    /** @var array<string> */
    public array $sectionOrder = [];

    public function mount(): void
    {
        $store = app('current_store');
        $service = app(ThemeSettingsService::class);
        $this->themeSettings = $service->getSettings($store);
        $this->sectionOrder = $this->themeSettings['section_order'] ?? [];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Collection>
     */
    public function getFeaturedCollectionsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->themeSettings['sections']['featured_collections']['collection_ids'] ?? [];

        if (empty($ids)) {
            return Collection::query()->limit(4)->get();
        }

        return Collection::query()->whereIn('id', $ids)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function getFeaturedProductsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->themeSettings['sections']['featured_products']['product_ids'] ?? [];

        if (empty($ids)) {
            return Product::query()->where('status', 'active')->limit(8)->get();
        }

        return Product::query()->whereIn('id', $ids)->get();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.home')
            ->layout('layouts::storefront');
    }
}
