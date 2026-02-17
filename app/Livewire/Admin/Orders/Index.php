<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Orders')]
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
        $store = app('current_store');

        return Order::query()
            ->where('store_id', $store->id)
            ->with('customer')
            ->when($this->search, fn ($q) => $q->where(function ($q): void {
                $q->where('order_number', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter !== 'all', function ($q): void {
                match ($this->statusFilter) {
                    'pending' => $q->where('financial_status', 'pending'),
                    'paid' => $q->where('financial_status', 'paid'),
                    'fulfilled' => $q->where('fulfillment_status', 'fulfilled'),
                    'cancelled' => $q->where('status', 'cancelled'),
                    'refunded' => $q->where('financial_status', 'refunded'),
                    default => null,
                };
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.orders.index');
    }
}
