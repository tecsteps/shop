<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use Livewire\Component;

class Index extends Component
{
    public function render(): \Illuminate\View\View
    {
        $collections = Collection::query()->get();

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ])->layout('layouts.storefront.app', [
            'title' => 'Collections',
        ]);
    }
}
