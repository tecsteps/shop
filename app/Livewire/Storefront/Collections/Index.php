<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\ProductStatus;
use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Collections')]
class Index extends Component
{
    public function render()
    {
        $store = app('current_store');

        $collections = Collection::where('store_id', $store->id)
            ->active()
            ->withCount(['products' => function ($query) {
                $query->where('status', ProductStatus::Active);
            }])
            ->orderBy('title')
            ->get();

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ]);
    }
}
