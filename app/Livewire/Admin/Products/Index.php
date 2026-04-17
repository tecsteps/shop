<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    /** @var list<int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function bulkArchive(): void
    {
        if (empty($this->selected)) {
            return;
        }

        Product::query()
            ->whereIn('id', $this->selected)
            ->update(['status' => ProductStatus::Archived]);

        $this->selected = [];
    }

    public function render(): View
    {
        $query = Product::query();

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        $products = $query->latest()->paginate(15);

        return view('livewire.admin.products.index', [
            'products' => $products,
        ]);
    }
}
