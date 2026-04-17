<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $financialFilter = 'all';

    public string $fulfillmentFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFinancialFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFulfillmentFilter(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $query = Order::query()
            ->with('customer')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->financialFilter !== 'all', fn ($q) => $q->where('financial_status', $this->financialFilter))
            ->when($this->fulfillmentFilter !== 'all', fn ($q) => $q->where('fulfillment_status', $this->fulfillmentFilter))
            ->latest('placed_at');

        return view('livewire.admin.orders.index', [
            'orders' => $query->paginate(20),
        ])->layout('layouts.admin.app', [
            'title' => 'Orders',
        ]);
    }
}
