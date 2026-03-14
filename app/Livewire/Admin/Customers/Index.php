<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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
        $query = Customer::query()
            ->withCount('orders')
            ->withSum('orders', 'total_amount');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.customers.index')
            ->layout('layouts.admin', ['title' => 'Customers']);
    }
}
