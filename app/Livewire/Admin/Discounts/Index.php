<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $statusFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getDiscountsProperty()
    {
        $query = Discount::withoutGlobalScopes()
            ->where('store_id', session('store_id'));

        if ($this->search) {
            $query->where('code', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.discounts.index', [
            'discounts' => $this->discounts,
        ]);
    }
}
