<?php

namespace App\Livewire\Storefront\Collections;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    use WithPagination;

    public string $handle;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sort = 'default';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        if (! Schema::hasTable('collections') || ! app()->bound('current_store')) {
            throw new NotFoundHttpException('Collections not available');
        }

        $storeId = app('current_store')->id;

        $collection = DB::table('collections')
            ->where('store_id', $storeId)
            ->where('handle', $this->handle)
            ->where('status', 'active')
            ->first(['id', 'title', 'handle', 'description_html']);

        if (! $collection) {
            throw new NotFoundHttpException('Collection not found');
        }

        $products = $this->loadProducts($collection->id);

        return view('livewire.storefront.collections.show', [
            'collection' => $collection,
            'products' => $products,
        ]);
    }

    protected function loadProducts(int $collectionId): LengthAwarePaginator
    {
        if (! Schema::hasTable('products')) {
            return new LengthAwarePaginator([], 0, 12, 1);
        }

        $query = DB::table('products')
            ->join('collection_products', 'collection_products.product_id', '=', 'products.id')
            ->where('collection_products.collection_id', $collectionId)
            ->where('products.status', 'active');

        if ($this->search !== '') {
            $query->where('products.title', 'like', '%'.$this->search.'%');
        }

        match ($this->sort) {
            'title-asc' => $query->orderBy('products.title'),
            'title-desc' => $query->orderByDesc('products.title'),
            'newest' => $query->orderByDesc('products.created_at'),
            default => $query->orderBy('collection_products.position'),
        };

        return $query
            ->select('products.id', 'products.title', 'products.handle')
            ->paginate(12);
    }
}
