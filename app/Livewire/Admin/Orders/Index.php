<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $financialFilter = '';

    #[Url]
    public string $fulfillmentFilter = '';

    public int $perPage = 20;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFinancialFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFulfillmentFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $orders = Order::query()
            ->with('customer')
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($query): void {
                    $query->where('order_number', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->financialFilter !== '', fn ($q) => $q->where('financial_status', $this->financialFilter))
            ->when($this->fulfillmentFilter !== '', fn ($q) => $q->where('fulfillment_status', $this->fulfillmentFilter))
            ->latest('placed_at')
            ->paginate($this->perPage);

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
        ]);
    }
}
