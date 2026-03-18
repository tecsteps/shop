<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
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

    #[Url]
    public string $financialFilter = 'all';

    #[Url]
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

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $store = app('current_store');

        return Order::query()
            ->where('store_id', $store->id)
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->financialFilter !== 'all', fn ($q) => $q->where('financial_status', $this->financialFilter))
            ->when($this->fulfillmentFilter !== 'all', fn ($q) => $q->where('fulfillment_status', $this->fulfillmentFilter))
            ->with('customer')
            ->latest('placed_at')
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.admin.orders.index');
    }
}
