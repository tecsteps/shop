<?php

namespace App\Livewire\Storefront\Account\Addresses;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingAddressId = null;

    #[Validate('nullable|string|max:50')]
    public string $label = '';

    #[Validate('required|string|max:255')]
    public string $firstName = '';

    #[Validate('required|string|max:255')]
    public string $lastName = '';

    #[Validate('required|string|max:255')]
    public string $address1 = '';

    #[Validate('nullable|string|max:255')]
    public string $address2 = '';

    #[Validate('required|string|max:255')]
    public string $city = '';

    #[Validate('required|string|max:10')]
    public string $zip = '';

    #[Validate('required|string|size:2')]
    public string $countryCode = 'DE';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    public bool $isDefault = false;

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $addressId): void
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();

        $address = $customer->addresses()->findOrFail($addressId);

        $this->editingAddressId = $address->id;
        $this->label = $address->label ?? '';

        /** @var array<string, string> $json */
        $json = $address->address_json ?? [];
        $this->firstName = $json['first_name'] ?? '';
        $this->lastName = $json['last_name'] ?? '';
        $this->address1 = $json['address1'] ?? '';
        $this->address2 = $json['address2'] ?? '';
        $this->city = $json['city'] ?? '';
        $this->zip = $json['zip'] ?? '';
        $this->countryCode = $json['country_code'] ?? 'DE';
        $this->phone = $json['phone'] ?? '';
        $this->isDefault = $address->is_default;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();

        $addressData = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address1' => $this->address1,
            'address2' => $this->address2 ?: null,
            'city' => $this->city,
            'country_code' => $this->countryCode,
            'zip' => $this->zip,
            'phone' => $this->phone ?: null,
        ];

        if ($this->isDefault) {
            $customer->addresses()->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            $address = $customer->addresses()->findOrFail($this->editingAddressId);
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
    }

    public function deleteAddress(int $addressId): void
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();

        $customer->addresses()->where('id', $addressId)->delete();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->editingAddressId = null;
        $this->label = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->address1 = '';
        $this->address2 = '';
        $this->city = '';
        $this->zip = '';
        $this->countryCode = 'DE';
        $this->phone = '';
        $this->isDefault = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $customer->addresses()->get(),
        ]);
    }
}
