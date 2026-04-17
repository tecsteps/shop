<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
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
        $this->authorize('viewAny', Order::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query();

        if ($this->statusFilter !== 'all') {
            $query->where('financial_status', $this->statusFilter);
        }

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('order_number', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        return view('livewire.admin.orders.index', [
            'orders' => $query->orderByDesc('placed_at')->paginate(20),
        ]);
    }
}
