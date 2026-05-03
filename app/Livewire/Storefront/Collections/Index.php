<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.collections.index', [
            'collections' => Collection::query()
                ->where('status', CollectionStatus::Active)
                ->orderBy('title')
                ->get(),
        ])->layout('storefront.layouts.app', [
            'title' => 'Collections',
        ]);
    }
}
