<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    use WithPagination;

    public Collection $collection;

    #[Url(as: 'sort')]
    public string $sort = 'newest';

    public function mount(string $handle): void
    {
        $this->collection = Collection::query()
            ->where('handle', $handle)
            ->firstOrFail();
    }

    public function render()
    {
        $products = $this->collection->products()
            ->published()
            ->with('defaultVariant', 'media')
            ->when($this->sort === 'newest', fn ($q) => $q->orderByDesc('products.published_at'))
            ->when($this->sort === 'price_asc', fn ($q) => $q->orderBy('products.title'))
            ->when($this->sort === 'price_desc', fn ($q) => $q->orderByDesc('products.title'))
            ->paginate(12);

        return view('livewire.storefront.collections.show', compact('products'))
            ->title($this->collection->title);
    }
}
