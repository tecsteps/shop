<?php

namespace App\Livewire\Storefront\Account\Addresses;

use Livewire\Component;

class Index extends Component
{
    public string $label = '';

    public string $address1 = '';

    public string $city = '';

    public string $countryCode = 'DE';

    public string $postalCode = '';

    public function save(): void
    {
        $data = $this->validate(['label' => ['nullable', 'string', 'max:255'], 'address1' => ['required', 'string', 'max:500'], 'city' => ['required', 'string', 'max:255'], 'countryCode' => ['required', 'size:2'], 'postalCode' => ['required', 'max:20']]);
        auth('customer')->user()->addresses()->create(['label' => $data['label'], 'address_json' => ['address1' => $data['address1'], 'city' => $data['city'], 'country_code' => $data['countryCode'], 'postal_code' => $data['postalCode']], 'is_default' => auth('customer')->user()->addresses()->count() === 0]);
        $this->reset(['label', 'address1', 'city', 'postalCode']);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.addresses.index', ['addresses' => auth('customer')->user()->addresses()->latest()->get()])->layout('layouts.storefront');
    }
}
