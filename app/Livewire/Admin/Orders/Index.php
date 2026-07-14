<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Admin\AdminComponent;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $sortField = 'placed_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorizeAction('viewAny', Order::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        abort_unless(in_array($status, ['all', 'pending', 'paid', 'fulfilled', 'cancelled', 'refunded'], true), 400);
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        abort_unless(in_array($field, ['placed_at', 'order_number', 'total_amount'], true), 400);
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->sortField = $field;
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::query()->with('customer')
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested->where('order_number', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%'.$this->search.'%'))))
            ->when($this->statusFilter !== 'all', function (Builder $query): void {
                $query->where(function (Builder $nested): void {
                    $nested->where('status', $this->statusFilter)->orWhere('financial_status', $this->statusFilter)->orWhere('fulfillment_status', $this->statusFilter);
                });
            })->orderBy($this->sortField, $this->sortDirection)->paginate(25);
    }

    public function render(): View
    {
        return $this->admin(view('admin.orders.index'), 'Orders', [['label' => 'Orders']]);
    }
}
