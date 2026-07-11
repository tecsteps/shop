<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\AdminComponent;
use App\Models\Customer;
use Illuminate\Support\Facades\Gate;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Show extends AdminComponent
{
    public int $customerId;

    public function mount(Customer $customer): void
    {
        Gate::authorize('view', $customer);
        abort_unless($customer->store_id === $this->currentStore()->getKey(), 404);
        $this->customerId = $customer->getKey();
    }

    public function render()
    {
        $customer = Customer::query()->where('store_id', $this->currentStore()->getKey())->with(['addresses', 'orders' => fn ($query) => $query->latest('placed_at')])->findOrFail($this->customerId);

        return view('livewire.admin.customers.show', ['customer' => $customer]);
    }
}
