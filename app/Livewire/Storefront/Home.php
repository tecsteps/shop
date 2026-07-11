<?php

namespace App\Livewire\Storefront;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Home extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.home', [
            'collections' => Collection::query()->where('status', 'active')->withCount('products')->latest()->limit(3)->get(),
            'products' => Product::published()->with(['variants.inventoryItem', 'media'])->latest('published_at')->limit(8)->get(),
        ])->layout('layouts.storefront', ['title' => app('current_store')->name]);
    }
}
