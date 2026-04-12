<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\ProductStatus;
use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Collection as CollectionModel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    use EnsuresStore, WithPagination;

    public string $handle = '';

    #[Url]
    public string $sort = 'newest';

    public function mount(string $handle): void
    {
        $this->ensureCurrentStore();
        $this->handle = $handle;
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $collection = CollectionModel::query()
            ->where('handle', $this->handle)
            ->firstOrFail();

        $query = $collection->products()
            ->where('products.status', ProductStatus::Active->value);

        if ($this->sort === 'title_asc') {
            $query->orderBy('products.title');
        } elseif ($this->sort === 'newest') {
            $query->orderByDesc('products.id');
        } else {
            $query->orderBy('collection_products.position');
        }

        $products = $query->with('variants')->paginate(12);

        return view('livewire.storefront.collections.show', [
            'collection' => $collection,
            'products' => $products,
        ]);
    }
}
