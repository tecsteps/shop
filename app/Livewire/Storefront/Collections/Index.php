<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Models\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesStore, WithPagination;

    public function render()
    {
        $store = $this->store();

        $collections = Collection::query()
            ->where('store_id', $store->getKey())
            ->where('status', CollectionStatus::Active)
            ->withCount(['products' => fn ($query) => $query->where('status', \App\Enums\ProductStatus::Active)->whereNotNull('published_at')])
            ->orderBy('title')
            ->paginate(12);

        return view('livewire.storefront.collections.index', [
            'store' => $store,
            'collections' => $collections,
        ])->layout('storefront.layouts.app', [
            'title' => 'Collections - '.$store->name,
            'metaDescription' => 'Browse all collections at '.$store->name,
        ]);
    }
}
