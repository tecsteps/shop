<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Customer::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $customers = $this->customersQuery()
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->paginate(15);

        /** @var \App\Models\Store $store */
        $store = app('current_store');

        return view('livewire.admin.customers.index', [
            'customers' => $customers,
            'hasCustomers' => Customer::query()->exists(),
            'currency' => $store->default_currency,
        ])->layout('admin.layouts.app')->title('Customers');
    }

    /**
     * Customers matching the name/email search (spec 03 §9.1).
     *
     * @return Builder<Customer>
     */
    private function customersQuery(): Builder
    {
        return Customer::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.addcslashes($this->search, '\\%_').'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->latest('created_at');
    }
}
