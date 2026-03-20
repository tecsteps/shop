<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $customers = Customer::query()
            ->withCount('orders')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.customers.index', ['customers' => $customers])
            ->layout('layouts.admin.app', ['title' => 'Customers']);
    }
}
