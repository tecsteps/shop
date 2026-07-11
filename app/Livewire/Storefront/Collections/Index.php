<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.collections.index', [
            'collections' => Collection::query()->where('status', 'active')->withCount('products')->latest()->get(),
        ])->layout('layouts.storefront', ['title' => 'Collections - '.app('current_store')->name]);
    }
}
