<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Support\CurrencyFormatter;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    use CurrencyFormatter;

    public Customer $customer;

    // Address modal
    public ?int $editAddressId = null;

    public string $addressLabel = '';

    public string $address1 = '';

    public string $city = '';

    public string $zip = '';

    public string $country = '';

    public bool $isDefault = false;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load(['orders', 'addresses']);
    }

    public function openAddAddress(): void
    {
        $this->resetAddressForm();
        $this->editAddressId = null;
        $this->modal('address-form')->show();
    }

    public function editAddress(int $addressId): void
    {
        $address = $this->customer->addresses->firstWhere('id', $addressId);
        if (! $address) {
            return;
        }

        $this->editAddressId = $addressId;
        $this->addressLabel = $address->label ?? '';
        $addrData = $address->address_json ?? [];
        $this->address1 = data_get($addrData, 'address1', '');
        $this->city = data_get($addrData, 'city', '');
        $this->zip = data_get($addrData, 'zip', '');
        $this->country = data_get($addrData, 'country', '');
        $this->isDefault = (bool) $address->is_default;
        $this->modal('address-form')->show();
    }

    public function saveAddress(): void
    {
        $this->validate([
            'addressLabel' => ['required', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
        ]);

        $addressJson = [
            'address1' => $this->address1,
            'city' => $this->city,
            'zip' => $this->zip,
            'country' => $this->country,
        ];

        if ($this->isDefault) {
            $this->customer->addresses()->update(['is_default' => false]);
        }

        if ($this->editAddressId) {
            CustomerAddress::where('id', $this->editAddressId)->update([
                'label' => $this->addressLabel,
                'address_json' => $addressJson,
                'is_default' => $this->isDefault,
            ]);
        } else {
            CustomerAddress::create([
                'customer_id' => $this->customer->id,
                'label' => $this->addressLabel,
                'address_json' => $addressJson,
                'is_default' => $this->isDefault,
            ]);
        }

        $this->customer->refresh();
        $this->customer->load('addresses');
        $this->resetAddressForm();
        $this->dispatch('toast', type: 'success', message: 'Address saved.');
        $this->modal('address-form')->close();
    }

    public function deleteAddress(int $addressId): void
    {
        CustomerAddress::where('id', $addressId)->delete();
        $this->customer->refresh();
        $this->customer->load('addresses');
        $this->dispatch('toast', type: 'success', message: 'Address deleted.');
    }

    public function setDefaultAddress(int $addressId): void
    {
        $this->customer->addresses()->update(['is_default' => false]);
        CustomerAddress::where('id', $addressId)->update(['is_default' => true]);
        $this->customer->refresh();
        $this->customer->load('addresses');
        $this->dispatch('toast', type: 'success', message: 'Default address updated.');
    }

    #[Computed]
    public function totalSpent(): int
    {
        return (int) $this->customer->orders()->sum('total_amount');
    }

    public function render(): mixed
    {
        return view('livewire.admin.customers.show')
            ->layout('layouts.admin', [
                'breadcrumbs' => [
                    ['label' => 'Customers', 'url' => route('admin.customers.index')],
                    ['label' => $this->customer->name],
                ],
            ]);
    }

    protected function resetAddressForm(): void
    {
        $this->editAddressId = null;
        $this->addressLabel = '';
        $this->address1 = '';
        $this->city = '';
        $this->zip = '';
        $this->country = '';
        $this->isDefault = false;
    }
}
