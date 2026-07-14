<?php

namespace App\Livewire\Storefront\Collections;

use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Collection;
use Illuminate\View\View;

class Index extends StorefrontComponent
{
    public function render(): View
    {
        $collections = Collection::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->where('status', 'active')
            ->withCount(['products' => fn ($query) => $query->where('status', 'active')])
            ->with(['products' => fn ($query) => $query->where('status', 'active')->with('media')->limit(1)])
            ->orderBy('title')
            ->get();

        return $this->storefront(
            view('storefront.collections.index', compact('collections')),
            'Collections - '.$this->currentStore()->name,
            'Browse all collections from '.$this->currentStore()->name.'.',
        );
    }
}
