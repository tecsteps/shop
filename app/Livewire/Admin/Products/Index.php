<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(SearchService $search): View
    {
        $this->authorize('viewAny', Product::class);

        $term = trim($this->search);
        $store = app('current_store');

        $query = Product::query()->with(['variants.inventoryItem', 'media']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if (mb_strlen($term) >= 2) {
            $matched = $search->search($store, $term, log: false);
            $ids = $matched->pluck('id')->all();

            if ($ids === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $ids);
            }
        }

        /** @var LengthAwarePaginator<Product> $products */
        $products = $query->orderByDesc('updated_at')->paginate(20);

        return view('livewire.admin.products.index', [
            'products' => $products,
            'statuses' => array_merge(['all'], ProductStatus::values()),
        ]);
    }
}
