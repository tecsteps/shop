<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->orderBy('title')
            ->limit(4)
            ->get();

        $products = Product::query()
            ->where('status', ProductStatus::Active)
            ->with(['variants', 'media'])
            ->latest('published_at')
            ->limit(8)
            ->get();

        return view('livewire.storefront.home', [
            'collections' => $collections,
            'products' => $products,
        ])
            ->layout('layouts.storefront')
            ->title(app('current_store')->name);
    }
}
