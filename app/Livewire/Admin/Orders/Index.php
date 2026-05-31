<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Order list: filterable by financial status, searchable by order number or
 * customer email, sortable, and paginated. Reads are store-scoped.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

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

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    public function getOrdersProperty()
    {
        return Order::query()
            ->with('customer')
            ->when($this->search !== '', fn (Builder $q) => $q->where(function (Builder $q): void {
                $q->where('order_number', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $this->applyStatusFilter($q))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    /**
     * Map a tab value to the column it filters. "fulfilled" filters on
     * fulfillment_status; the rest filter on financial_status / status.
     */
    private function applyStatusFilter(Builder $query): Builder
    {
        return match ($this->statusFilter) {
            'fulfilled' => $query->where('fulfillment_status', 'fulfilled'),
            'cancelled' => $query->where('status', 'cancelled'),
            'pending' => $query->where('financial_status', 'pending'),
            'paid' => $query->where('financial_status', 'paid'),
            'refunded' => $query->whereIn('financial_status', ['refunded', 'partially_refunded']),
            default => $query,
        };
    }

    public function render()
    {
        return view('livewire.admin.orders.index');
    }
}
