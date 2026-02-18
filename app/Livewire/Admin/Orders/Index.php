<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

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
        /** @var Store $store */
        $store = app('current_store');

        $query = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
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

        $orders = $query->latest('placed_at')->paginate(15);

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
        ]);
    }
}
