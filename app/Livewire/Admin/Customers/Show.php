<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Component;

class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load(['orders', 'addresses']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.customers.show')->layout('layouts.admin');
    }
}
