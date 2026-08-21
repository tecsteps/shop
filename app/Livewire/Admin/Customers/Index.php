<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
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

    public function render(): mixed
    {
        $search = trim($this->search);
        $customers = Customer::query()
            ->select(['id', 'store_id', 'first_name', 'last_name', 'email', 'created_at'])
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('email', 'like', '%'.$search.'%')
                        ->orWhere('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.customers.index', compact('customers'))->layout('layouts.admin');
    }
}
