<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Customer'])]
class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load(['addresses', 'orders' => fn ($q) => $q->latest()->limit(10)]);
    }

    public function render(): mixed
    {
        return view('livewire.admin.customers.show');
    }
}
