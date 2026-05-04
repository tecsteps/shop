<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;

class Index extends Component
{
    public function collections(): SupportCollection
    {
        return Collection::query()
            ->withCount(['products' => fn ($query) => $query
                ->where('status', 'active')
                ->whereNotNull('published_at')])
            ->where('status', 'active')
            ->orderBy('title')
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.collections.index', [
            'collections' => $this->collections(),
        ])->layout('layouts.storefront', [
            'title' => __('Collections'),
        ]);
    }
}
