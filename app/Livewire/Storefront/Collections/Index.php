<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    /**
     * Render the grid of all active collections.
     */
    public function render(): View
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->latest()
            ->get();

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ])
            ->layout('storefront.layouts.app', [
                'metaDescription' => 'Browse all collections of '.app('current_store')->name,
            ])
            ->title('Collections - '.app('current_store')->name);
    }
}
