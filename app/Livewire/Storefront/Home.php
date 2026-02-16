<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Home extends Component
{
    public function render(): mixed
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        $featuredProducts = Product::query()
            ->where('status', ProductStatus::Active)
            ->with('variants', 'media')
            ->latest()
            ->limit(8)
            ->get();

        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->limit(4)
            ->get();

        return view('livewire.storefront.home', [
            'storeName' => $store?->name ?? config('app.name'),
            'featuredProducts' => $featuredProducts,
            'collections' => $collections,
        ]);
    }
}
