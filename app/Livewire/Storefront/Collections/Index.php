<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->orderBy('title')
            ->paginate(12);

        return view('livewire.storefront.collections.index', ['collections' => $collections])
            ->layout('layouts.storefront')
            ->title('Collections - '.app('current_store')->name);
    }
}
