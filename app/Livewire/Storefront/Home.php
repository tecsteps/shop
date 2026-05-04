<?php

namespace App\Livewire\Storefront;

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;

class Home extends Component
{
    public function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    public function featuredProducts(): SupportCollection
    {
        return Product::query()
            ->with(['variants.inventoryItem'])
            ->withCount('variants')
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->oldest('id')
            ->limit((int) data_get($this->themeSettings(), 'home.featured_product_limit', 8))
            ->get();
    }

    public function featuredCollections(): SupportCollection
    {
        return Collection::query()
            ->withCount('products')
            ->where('status', 'active')
            ->orderBy('title')
            ->limit((int) data_get($this->themeSettings(), 'home.featured_collection_limit', 4))
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function themeSettings(): array
    {
        return app(ThemeSettingsService::class)->forStore($this->store());
    }

    public function render(): mixed
    {
        return view('livewire.storefront.home', [
            'store' => $this->store(),
            'featuredProducts' => $this->featuredProducts(),
            'featuredCollections' => $this->featuredCollections(),
            'themeSettings' => $this->themeSettings(),
        ])->layout('layouts.storefront', [
            'title' => $this->store()->name,
        ]);
    }
}
