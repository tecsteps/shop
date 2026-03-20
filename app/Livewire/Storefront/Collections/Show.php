<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\ProductStatus;
use App\Models\Collection;
use Livewire\Component;

class Show extends Component
{
    public string $handle = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render(): \Illuminate\View\View
    {
        $collection = Collection::query()
            ->where('handle', $this->handle)
            ->firstOrFail();

        $products = $collection->products()
            ->where('status', ProductStatus::Active)
            ->with(['variants' => fn ($q) => $q->where('is_default', true), 'media'])
            ->get();

        return view('livewire.storefront.collections.show', [
            'collection' => $collection,
            'products' => $products,
        ])->layout('layouts.storefront.app', [
            'title' => $collection->title,
        ]);
    }
}
