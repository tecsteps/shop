<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function render(): View
    {
        $this->customer->load(['orders', 'addresses']);

        return view('livewire.admin.customers.show', [
            'customer' => $this->customer,
        ]);
    }
}
