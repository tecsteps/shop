<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

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

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['placed_at', 'total_amount'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->with('customer')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('order_number', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->dateFrom), fn ($query) => $query->whereDate('placed_at', '>=', $this->dateFrom))
            ->when(filled($this->dateTo), fn ($query) => $query->whereDate('placed_at', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.admin.orders.index')->title(__('Orders'));
    }
}
