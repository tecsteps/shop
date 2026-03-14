<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

class Index extends Component
{
    /** @var EloquentCollection<int, Collection> */
    public EloquentCollection $collections;

    public function mount(): void
    {
        $this->collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.collections.index')
            ->layout('layouts.storefront', ['title' => 'Collections']);
    }
}
