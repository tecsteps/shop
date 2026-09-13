<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesAdminStore, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $sortField = 'placed_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        Gate::authorize('viewAny', Order::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $query = Order::query()
            ->where('store_id', $this->store()->getKey())
            ->with('customer');

        if ($this->search !== '') {
            $query->where(function ($inner): void {
                $inner->where('order_number', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $sortable = ['placed_at', 'total_amount', 'order_number'];

        if (in_array($this->sortField, $sortable, true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderByDesc('placed_at');
        }

        return view('livewire.admin.orders.index', [
            'orders' => $query->paginate(25),
        ])->layout('livewire.admin.layout.app', ['title' => 'Orders']);
    }
}
