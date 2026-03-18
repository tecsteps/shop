<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Address Book')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingAddressId = null;

    public string $label = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $postalCode = '';

    public string $countryCode = 'DE';

    public string $phone = '';

    public bool $isDefault = false;

    #[Computed]
    public function addresses(): \Illuminate\Database\Eloquent\Collection
    {
        return Auth::guard('customer')->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->get();
    }

    public function openAddForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingAddressId = null;
    }

    public function editAddress(int $addressId): void
    {
        $address = $this->findAddress($addressId);

        $this->editingAddressId = $address->id;
        $this->label = $address->label ?? '';
        $this->firstName = $address->address_json['first_name'] ?? '';
        $this->lastName = $address->address_json['last_name'] ?? '';
        $this->address1 = $address->address_json['address1'] ?? '';
        $this->address2 = $address->address_json['address2'] ?? '';
        $this->city = $address->address_json['city'] ?? '';
        $this->province = $address->address_json['province'] ?? '';
        $this->postalCode = $address->address_json['postal_code'] ?? '';
        $this->countryCode = $address->address_json['country_code'] ?? 'DE';
        $this->phone = $address->address_json['phone'] ?? '';
        $this->isDefault = $address->is_default;
        $this->showForm = true;
    }

    public function saveAddress(): void
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['required', 'string', 'max:20'],
            'countryCode' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:30'],
            'label' => ['nullable', 'string', 'max:50'],
        ]);

        $customer = Auth::guard('customer')->user();

        $addressData = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
            'phone' => $this->phone,
        ];

        if ($this->isDefault) {
            $customer->addresses()->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            $address = $this->findAddress($this->editingAddressId);
            $address->update([
                'label' => $this->label ?: null,
                'address_json' => $addressData,
                'is_default' => $this->isDefault,
            ]);
        } else {
            $customer->addresses()->create([
                'label' => $this->label ?: null,
                'address_json' => $addressData,
                'is_default' => $this->isDefault,
            ]);
        }

        $this->resetForm();
        $this->showForm = false;
        $this->editingAddressId = null;
        unset($this->addresses);
    }

    public function deleteAddress(int $addressId): void
    {
        $address = $this->findAddress($addressId);
        $address->delete();
        unset($this->addresses);
    }

    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = $this->findAddress($addressId);

        $customer->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        unset($this->addresses);
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
        $this->editingAddressId = null;
    }

    protected function findAddress(int $addressId): CustomerAddress
    {
        return Auth::guard('customer')->user()
            ->addresses()
            ->findOrFail($addressId);
    }

    protected function resetForm(): void
    {
        $this->reset([
            'label', 'firstName', 'lastName', 'address1', 'address2',
            'city', 'province', 'postalCode', 'phone', 'isDefault',
        ]);
        $this->countryCode = 'DE';
    }

    public function render(): View
    {
        return view('livewire.storefront.account.addresses.index')
            ->layout('storefront.layouts.app', ['title' => 'Address Book']);
    }
}
