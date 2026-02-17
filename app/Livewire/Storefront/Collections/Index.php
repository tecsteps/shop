<?php

namespace App\Livewire\Storefront\Collections;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    public function render(): \Illuminate\View\View
    {
        $collections = collect();

        if (app()->bound('current_store')) {
            $collections = \App\Models\Collection::withoutGlobalScopes()
                ->where('store_id', app('current_store')->id)
                ->where('status', 'active')
                ->orderBy('title')
                ->get();
        }

        $storeName = app()->bound('current_store') ? app('current_store')->name : config('app.name');

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ])->title("Collections - {$storeName}");
    }
}
