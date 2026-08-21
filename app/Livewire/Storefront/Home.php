<?php

namespace App\Livewire\Storefront;

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use Livewire\Component;

class Home extends Component
{
    public ?Store $store = null;

    public function mount(): void
    {
        $this->store = app()->bound('current_store') ? app('current_store') : null;
    }

    public function render(): mixed
    {
        if ($this->store === null) {
            return view('livewire.storefront.home-fallback')->layout('layouts.empty');
        }

        return view('livewire.storefront.home', ['collections' => Collection::query()->where('status', 'active')->withCount('products')->latest()->take(4)->get(), 'products' => Product::query()->published()->with(['variants.inventory', 'media'])->latest('published_at')->take(8)->get()])->layout('layouts.storefront');
    }
}
