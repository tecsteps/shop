<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public int $customerId;

    public string $name = '';

    public bool $marketingOptIn = false;

    public function mount(int $customer): void
    {
        $this->customerId = $customer;
        $saved = $this->customer();
        Gate::authorize('view', $saved);
        $this->name = $saved->name ?? '';
        $this->marketingOptIn = $saved->marketing_opt_in;
    }

    public function save(): void
    {
        $customer = $this->customer();
        Gate::authorize('update', $customer);
        $this->validate(['name' => ['nullable', 'string', 'max:255'], 'marketingOptIn' => ['boolean']]);
        $customer->update(['name' => $this->name, 'marketing_opt_in' => $this->marketingOptIn]);
        session()->flash('status', 'Customer saved.');
    }

    public function render(): mixed
    {
        $customer = $this->customer()->load('addresses', 'orders.lines');
        Gate::authorize('view', $customer);

        return view('livewire.admin.customers.show', ['customer' => $customer])->layout('layouts.admin');
    }

    private function customer(): Customer
    {
        return Customer::query()->findOrFail($this->customerId);
    }
}
