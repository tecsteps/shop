<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesAdminStore, WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Customer::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Customer::query()
            ->where('store_id', $this->store()->getKey())
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->orderByDesc('created_at');

        if ($this->search !== '') {
            $query->where(function ($inner): void {
                $inner->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        return view('livewire.admin.customers.index', [
            'customers' => $query->paginate(25),
        ])->layout('livewire.admin.layout.app', ['title' => 'Customers']);
    }
}
