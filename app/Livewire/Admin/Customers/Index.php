<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Customers')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<Customer>
     */
    #[Computed]
    public function customers(): LengthAwarePaginator
    {
        $store = app('current_store');

        return Customer::query()
            ->where('store_id', $store->id)
            ->when($this->search, fn ($q) => $q->where(function ($q): void {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->latest()
            ->paginate(15);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.customers.index');
    }
}
