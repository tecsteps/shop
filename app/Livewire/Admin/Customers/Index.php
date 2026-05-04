<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Locked]
    public int $storeId;

    public string $storeCurrency = 'EUR';

    public string $search = '';

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->storeId = $store->getKey();
        $this->storeCurrency = $store->default_currency;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function customers(): LengthAwarePaginator
    {
        return Customer::withoutGlobalScopes()
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total_amount')
            ->where('store_id', $this->storeId)
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->latest('created_at')
            ->paginate(20);
    }

    public function render(): mixed
    {
        return view('livewire.admin.customers.index', [
            'customers' => $this->customers(),
        ])->layout('layouts.app', [
            'title' => __('Customers'),
        ]);
    }
}
