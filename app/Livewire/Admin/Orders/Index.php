<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $financialFilter = '';

    public string $fulfillmentFilter = '';

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

    public function updatedFinancialFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFulfillmentFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Order::query()->with('customer');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->financialFilter !== '') {
            $query->where('financial_status', $this->financialFilter);
        }

        if ($this->fulfillmentFilter !== '') {
            $query->where('fulfillment_status', $this->fulfillmentFilter);
        }

        return view('livewire.admin.orders.index', [
            'orders' => $query->latest('placed_at')->paginate(15),
        ]);
    }
}
