<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Models\Order;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $financialStatus = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFinancialStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.orders.index', [
            'orders' => Order::query()
                ->with('customer')
                ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                    $query->where('order_number', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->when($this->financialStatus !== 'all', fn ($query) => $query->where('financial_status', $this->financialStatus))
                ->latest('placed_at')
                ->paginate(10),
            'financialStatuses' => FinancialStatus::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Orders',
        ]);
    }
}
