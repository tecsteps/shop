<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    use WithPagination;

    public Collection $collection;

    #[Url]
    public string $sort = 'newest';

    #[Url]
    public ?int $minPrice = null;

    #[Url]
    public ?int $maxPrice = null;

    public function mount(string $handle): void
    {
        $collection = Collection::query()
            ->where('handle', $handle)
            ->where('status', CollectionStatus::Active)
            ->first();

        if (! $collection) {
            abort(404);
        }

        $this->collection = $collection;
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = $this->collection->products()
            ->where('products.status', ProductStatus::Active)
            ->with(['variants' => function (mixed $q): void {
                /** @var \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductVariant, \App\Models\Product> $q */
                $q->where('is_default', true);
            }]);

        if ($this->minPrice !== null) {
            $query->whereHas('variants', fn ($q) => $q->where('is_default', true)->where('price_amount', '>=', $this->minPrice * 100));
        }

        if ($this->maxPrice !== null) {
            $query->whereHas('variants', fn ($q) => $q->where('is_default', true)->where('price_amount', '<=', $this->maxPrice * 100));
        }

        $query = match ($this->sort) {
            'price_asc' => $query->orderByRaw('(SELECT price_amount FROM product_variants WHERE product_variants.product_id = products.id AND is_default = 1 LIMIT 1) ASC'),
            'price_desc' => $query->orderByRaw('(SELECT price_amount FROM product_variants WHERE product_variants.product_id = products.id AND is_default = 1 LIMIT 1) DESC'),
            default => $query->orderBy('products.created_at', 'desc'),
        };

        $products = $query->paginate(12);

        return view('livewire.storefront.collections.show', [
            'products' => $products,
        ]);
    }
}
