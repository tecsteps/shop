<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\View\View;
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

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'financial')]
    public string $financialStatus = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFinancialStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $store = app('current_store');

        $orders = Order::query()
            ->where('store_id', $store->id)
            ->with('customer')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('customer', function ($cq) {
                            $cq->where('first_name', 'like', '%'.$this->search.'%')
                                ->orWhere('last_name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->financialStatus, fn ($query) => $query->where('financial_status', $this->financialStatus))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
            'orderStatuses' => OrderStatus::cases(),
            'financialStatuses' => FinancialStatus::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
        ]);
    }
}
