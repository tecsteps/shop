<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout', ['title' => 'Orders'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $store = app('current_store');

        $orders = Order::query()
            ->where('store_id', $store->id)
            ->with('customer')
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('order_number', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->when($this->status, fn ($q) => match ($this->status) {
                'pending' => $q->where('financial_status', 'pending'),
                'paid' => $q->where('financial_status', 'paid'),
                'fulfilled' => $q->where('fulfillment_status', 'fulfilled'),
                'cancelled' => $q->where('status', 'cancelled'),
                default => $q,
            })
            ->latest('placed_at')
            ->paginate(15);

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
        ]);
    }
}
