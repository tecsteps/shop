<?php

namespace App\Livewire\Storefront\Account\Addresses;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $postalCode = '';

    public string $country = 'DE';

    public string $phone = '';

    #[Computed]
    public function addresses()
    {
        return Auth::guard('customer')->user()->addresses()->orderByDesc('is_default')->get();
    }

    public function addNew(): void
    {
        $this->reset(['editingId', 'label', 'firstName', 'lastName', 'address1', 'address2', 'city', 'province', 'postalCode', 'phone']);
        $this->country = 'DE';
        $this->showModal = true;
    }

    public function edit(int $addressId): void
    {
        $address = Auth::guard('customer')->user()->addresses()->findOrFail($addressId);
        $data = $address->address_json;

        $this->editingId = $address->id;
        $this->label = $address->label ?? '';
        $this->firstName = $data['first_name'] ?? '';
        $this->lastName = $data['last_name'] ?? '';
        $this->address1 = $data['address1'] ?? '';
        $this->address2 = $data['address2'] ?? '';
        $this->city = $data['city'] ?? '';
        $this->province = $data['province'] ?? '';
        $this->postalCode = $data['postal_code'] ?? '';
        $this->country = $data['country'] ?? 'DE';
        $this->phone = $data['phone'] ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'address1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postalCode' => 'required|string|max:255',
            'country' => 'required|string|size:2',
        ]);

        $addressJson = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postalCode,
            'country' => strtoupper($this->country),
            'phone' => $this->phone,
        ];

        $customer = Auth::guard('customer')->user();
        $isFirstAddress = $customer->addresses()->count() === 0;

        if ($this->editingId) {
            $customer->addresses()->findOrFail($this->editingId)->update([
                'label' => $this->label ?: null,
                'address_json' => $addressJson,
            ]);
        } else {
            $customer->addresses()->create([
                'label' => $this->label ?: null,
                'address_json' => $addressJson,
                'is_default' => $isFirstAddress,
            ]);
        }

        unset($this->addresses);
        $this->showModal = false;
    }

    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $customer->addresses()->update(['is_default' => false]);
        $customer->addresses()->whereKey($addressId)->update(['is_default' => true]);
        unset($this->addresses);
    }

    public function delete(int $addressId): void
    {
        Auth::guard('customer')->user()->addresses()->whereKey($addressId)->delete();
        unset($this->addresses);
    }

    public function render()
    {
        return view('livewire.storefront.account.addresses.index')
            ->layout('layouts.storefront')
            ->title('Addresses - '.app('current_store')->name);
    }
}
