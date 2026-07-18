<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Customer details')]
class Show extends Component
{
    public Customer $customer;

    public ?int $editingAddressId = null;

    public string $addressLabel = '';

    public array $addressJson = ['address1' => '', 'address2' => '', 'city' => '', 'province' => '', 'postal_code' => '', 'country_code' => ''];

    public function mount(Customer $customer): void
    {
        Gate::authorize('view', $customer);
        $this->customer = $customer;
        $this->reloadCustomer();
    }

    public function editAddress(?int $addressId = null): void
    {
        $address = $addressId ? $this->customer->addresses()->findOrFail($addressId) : null;
        $this->editingAddressId = $address?->id;
        $this->addressLabel = $address?->label ?? '';
        $this->addressJson = array_merge(['address1' => '', 'address2' => '', 'city' => '', 'province' => '', 'postal_code' => '', 'country_code' => ''], $address?->address_json ?? []);
    }

    public function saveAddress(): void
    {
        Gate::authorize('update', $this->customer);
        $validated = $this->validate(['addressLabel' => ['required', 'string', 'max:255'], 'addressJson.address1' => ['required', 'string', 'max:500'], 'addressJson.city' => ['required', 'string', 'max:255'], 'addressJson.postal_code' => ['required', 'string', 'max:20'], 'addressJson.country_code' => ['required', 'string', 'size:2']]);
        $address = $this->editingAddressId ? $this->customer->addresses()->findOrFail($this->editingAddressId) : new CustomerAddress(['customer_id' => $this->customer->id]);
        $address->fill(['label' => $validated['addressLabel'], 'address_json' => $this->addressJson])->save();
        $this->reloadCustomer();
        $this->dispatch('toast', type: 'success', message: 'Address saved.');
    }

    public function deleteAddress(int $addressId): void
    {
        Gate::authorize('update', $this->customer);
        $this->customer->addresses()->findOrFail($addressId)->delete();
        $this->reloadCustomer();
    }

    public function setDefaultAddress(int $addressId): void
    {
        Gate::authorize('update', $this->customer);
        $this->customer->addresses()->update(['is_default' => false]);
        $this->customer->addresses()->findOrFail($addressId)->update(['is_default' => true]);
        $this->reloadCustomer();
    }

    private function reloadCustomer(): void
    {
        $this->customer->refresh()->load(['addresses', 'orders' => fn ($query) => $query->latest('placed_at')]);
    }

    public function render(): View
    {
        return view('livewire.admin.customers.show');
    }
}
