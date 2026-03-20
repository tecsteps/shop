<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Livewire\Component;

class Index extends Component
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Collection>
     */
    public function getCollectionsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Collection::query()
            ->where('status', CollectionStatus::Active)
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.collections.index')
            ->layout('layouts::storefront');
    }
}
