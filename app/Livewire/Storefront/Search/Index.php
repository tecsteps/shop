<?php

namespace App\Livewire\Storefront\Search;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    use EnsuresStore, WithPagination;

    #[Url(as: 'q')]
    public string $q = '';

    public function mount(): void
    {
        $this->ensureCurrentStore();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $store = $this->ensureCurrentStore();

        $products = trim($this->q) !== ''
            ? app(SearchService::class)->search($store, $this->q, [], 12)
            : Product::query()->whereRaw('1 = 0')->paginate(12);

        return view('livewire.storefront.search.index', [
            'products' => $products,
        ]);
    }
}
