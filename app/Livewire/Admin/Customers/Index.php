<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\AdminComponent;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $marketingFilter = 'all';

    public function mount(): void
    {
        Gate::authorize('viewAny', Customer::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers()
    {
        return Customer::query()->where('store_id', $this->currentStore()->getKey())->withCount('orders')->withSum('orders', 'total_amount')
            ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $nested) => $nested->where('name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')))
            ->when($this->marketingFilter !== 'all', fn (Builder $query) => $query->where('marketing_opt_in', $this->marketingFilter === 'subscribed'))
            ->latest()->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.customers.index');
    }
}
