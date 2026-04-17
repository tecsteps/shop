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
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->orderBy('title')
            ->get();

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ])->layout('storefront.layouts.app', [
            'title' => 'Collections',
        ]);
    }
}
