<?php

namespace App\Livewire\Storefront\Search;

use App\Enums\ProductStatus;
use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Product;
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
        $query = Product::query()
            ->where('status', ProductStatus::Active->value)
            ->with('variants');

        if (trim($this->q) !== '') {
            $query->where('title', 'like', '%'.$this->q.'%');
        } else {
            $query->whereRaw('1 = 0');
        }

        $products = $query->orderBy('title')->paginate(12);

        return view('livewire.storefront.search.index', [
            'products' => $products,
        ]);
    }
}
