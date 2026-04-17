<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

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

        if ($this->dateFrom !== '') {
            $query->where('placed_at', '>=', $this->dateFrom.' 00:00:00');
        }

        if ($this->dateTo !== '') {
            $query->where('placed_at', '<=', $this->dateTo.' 23:59:59');
        }

        $orders = $query->latest('placed_at')->paginate(15);

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
        ]);
    }
}
