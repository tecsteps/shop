<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $financialFilter = 'all';

    public string $fulfillmentFilter = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

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

    public function updatedFinancialFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFulfillmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle the sort column or flip the direction (spec 03 §7).
     */
    public function sortBy(string $field): void
    {
        if (! in_array($field, ['order_number', 'placed_at', 'total_amount'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render(): View
    {
        $orders = $this->ordersQuery()
            ->with('customer')
            ->paginate(15);

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
            'hasOrders' => Order::query()->exists(),
        ])->layout('admin.layouts.app')->title('Orders');
    }

    /**
     * Base query with search, status filters, date range, and sorting
     * (spec 03 §7).
     *
     * @return Builder<Order>
     */
    private function ordersQuery(): Builder
    {
        return Order::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.addcslashes($this->search, '\\%_').'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('order_number', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhereHas('customer', fn (Builder $customer) => $customer
                            ->where('email', 'like', $term)
                            ->orWhere('name', 'like', $term));
                });
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->financialFilter !== 'all', fn (Builder $query) => $query->where('financial_status', $this->financialFilter))
            ->when($this->fulfillmentFilter !== 'all', fn (Builder $query) => $query->where('fulfillment_status', $this->fulfillmentFilter))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('placed_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('placed_at', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('id', $this->sortDirection);
    }
}
