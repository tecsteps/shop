<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public string $handle = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render(): View
    {
        $collection = Collection::query()
            ->with(['products' => fn ($query) => $query
                ->with('variants', 'media')
                ->where('status', ProductStatus::Active)
                ->whereNotNull('published_at')
                ->orderBy('collection_products.position')])
            ->where('handle', $this->handle)
            ->where('status', CollectionStatus::Active)
            ->firstOrFail();

        return view('livewire.storefront.collections.show', [
            'collection' => $collection,
        ])->layout('storefront.layouts.app', [
            'title' => $collection->title,
        ]);
    }
}
