<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Services\StorefrontCatalog;
use Livewire\Component;

class FeaturedProducts extends Component
{
    use ResolvesStore;

    public int $count = 8;

    public ?int $collectionId = null;

    public function mount(int $count = 8, ?int $collectionId = null): void
    {
        $this->count = $count;
        $this->collectionId = $collectionId;
    }

    public function render()
    {
        $catalog = app(StorefrontCatalog::class);
        $query = $catalog->visibleProducts($this->store());

        if ($this->collectionId !== null) {
            $query->whereHas('collections', fn ($query) => $query->where('collections.id', $this->collectionId));
        }

        $products = $catalog->withStorefrontRelations($query)->orderBy('id')->take($this->count)->get();

        return view('livewire.storefront.featured-products', ['products' => $products]);
    }
}
