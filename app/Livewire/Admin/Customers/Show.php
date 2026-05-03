<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function render(): View
    {
        $this->customer->load('addresses', 'orders.lines');

        return view('livewire.admin.customers.show')->layout('livewire.admin.layout.app', [
            'title' => $this->customer->name ?? $this->customer->email,
        ]);
    }
}
