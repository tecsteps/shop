<?php

namespace App\Livewire\Storefront;

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    public function mount(): void
    {
        if (! app()->bound('current_store')) {
            $store = Store::first();

            if ($store !== null) {
                app()->instance('current_store', $store);
            }
        }
    }

    public function render(): View
    {
        return view('livewire.storefront.home', [
            'featuredCollections' => $this->featuredCollections(),
            'recentProducts' => $this->recentProducts(),
        ]);
    }

    /**
     * @return SupportCollection<int, Collection>
     */
    protected function featuredCollections(): SupportCollection
    {
        if (! class_exists(Collection::class)) {
            return collect();
        }

        return Collection::query()
            ->latest()
            ->limit(3)
            ->get();
    }

    /**
     * @return SupportCollection<int, Product>
     */
    protected function recentProducts(): SupportCollection
    {
        if (! class_exists(Product::class)) {
            return collect();
        }

        return Product::query()
            ->with('variants')
            ->latest()
            ->limit(8)
            ->get();
    }
}
