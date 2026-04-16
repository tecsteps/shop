<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load('addresses', 'orders');
    }

    public function render()
    {
        return view('livewire.admin.customers.show')->title($this->customer->name ?? $this->customer->email);
    }
}
