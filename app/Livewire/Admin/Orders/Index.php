<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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

    /**
     * @return LengthAwarePaginator<Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $query = Order::query()->with('customer');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== 'all') {
            $status = OrderStatus::tryFrom($this->statusFilter);
            if ($status) {
                $query->where('status', $status);
            }
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.orders.index')
            ->layout('layouts.admin', ['title' => 'Orders']);
    }
}
