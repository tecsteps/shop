<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Support\CurrencyFormatter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use CurrencyFormatter;
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers(): mixed
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

        return $query->latest('created_at')->paginate(15);
    }

    public function render(): mixed
    {
        return view('livewire.admin.customers.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Customers']]]);
    }
}
