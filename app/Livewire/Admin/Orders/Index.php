<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
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

    #[Url]
    public string $statusFilter = 'all';

    public string $sortField = 'placed_at';

    public string $sortDirection = 'desc';

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

    public function getOrdersProperty()
    {
        $query = Order::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->with('customer');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(20);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.orders.index', [
            'orders' => $this->orders,
        ]);
    }
}
