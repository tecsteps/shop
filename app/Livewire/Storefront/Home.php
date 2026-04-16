<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    public function render()
    {
        $featured = Product::query()->published()->with('defaultVariant', 'media')->limit(8)->get();
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active->value)
            ->limit(4)
            ->get();

        return view('livewire.storefront.home', compact('featured', 'collections'));
    }
}
