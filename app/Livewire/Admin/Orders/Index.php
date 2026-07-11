<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Admin\AdminComponent;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        Gate::authorize('viewAny', Order::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders()
    {
        return Order::query()->where('store_id', $this->currentStore()->getKey())->with('customer')
            ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $nested) => $nested->where('order_number', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')))
            ->when($this->statusFilter !== 'all', function (Builder $query): void {
                if (in_array($this->statusFilter, ['paid', 'pending', 'refunded', 'partially_refunded'], true)) {
                    $query->where('financial_status', $this->statusFilter);
                } else {
                    $query->where('fulfillment_status', $this->statusFilter);
                }
            })->orderBy('placed_at', $this->sortDirection)->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.orders.index');
    }
}
