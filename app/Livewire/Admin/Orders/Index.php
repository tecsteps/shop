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

    public string $sortField = 'placed_at';

    public string $sortDirection = 'desc';

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

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['order_number', 'placed_at', 'total_amount'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'order_number' ? 'asc' : 'desc';
        }

        $this->resetPage();
    }

    public function render(): mixed
    {
        $search = trim($this->search);

        $orders = Order::query()
            ->select(['id', 'store_id', 'customer_id', 'order_number', 'email', 'status', 'financial_status', 'fulfillment_status', 'total_amount', 'currency', 'placed_at'])
            ->with('customer:id,store_id,first_name,last_name,email')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search): void {
                            $customerQuery->where(function ($customerQuery) use ($search): void {
                                $customerQuery->where('first_name', 'like', '%'.$search.'%')
                                    ->orWhere('last_name', 'like', '%'.$search.'%')
                                    ->orWhere('email', 'like', '%'.$search.'%');
                            });
                        });
                });
            })
            ->when($this->statusFilter !== 'all', function ($query): void {
                match ($this->statusFilter) {
                    'pending' => $query->where('financial_status', 'pending'),
                    'paid' => $query->where('financial_status', 'paid'),
                    'fulfilled' => $query->where(function ($query): void {
                        $query->where('status', 'fulfilled')->orWhere('fulfillment_status', 'fulfilled');
                    }),
                    'cancelled' => $query->where('status', 'cancelled'),
                    'refunded' => $query->where('financial_status', 'refunded'),
                    default => null,
                };
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.admin.orders.index', compact('orders'))->layout('layouts.admin');
    }
}
