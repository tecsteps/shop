<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Component;

class Show extends Component
{
    public int $customerId;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function render(): mixed
    {
        $customer = Customer::with(['orders' => fn ($q) => $q->withoutGlobalScopes()->latest('placed_at')->limit(10), 'addresses'])
            ->findOrFail($this->customerId);

        return view('livewire.admin.customers.show', ['customer' => $customer])
            ->layout('layouts.admin.app', ['title' => $customer->name]);
    }
}
