<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public function render(): mixed
    {
        $customers = Customer::query()->when($this->search !== '', fn ($query) => $query->where('email', 'like', '%'.$this->search.'%')->orWhere('first_name', 'like', '%'.$this->search.'%')->orWhere('last_name', 'like', '%'.$this->search.'%'))->withCount('orders')->latest()->paginate(15);

        return view('livewire.admin.customers.index', compact('customers'))->layout('layouts.admin');
    }
}
