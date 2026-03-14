<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    /** @var Collection<int, CustomerAddress> */
    public Collection $addresses;

    public bool $showForm = false;

    public ?int $editingAddressId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $postal_code = '';

    public string $country_code = 'DE';

    public string $phone = '';

    public bool $is_default = false;

    /** @var array<string, list<string>> */
    protected array $rules = [
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'address1' => ['required', 'string', 'max:255'],
        'address2' => ['nullable', 'string', 'max:255'],
        'city' => ['required', 'string', 'max:255'],
        'province' => ['nullable', 'string', 'max:255'],
        'postal_code' => ['required', 'string', 'max:20'],
        'country_code' => ['required', 'string', 'size:2'],
        'phone' => ['nullable', 'string', 'max:50'],
        'is_default' => ['boolean'],
    ];

    public function mount(): void
    {
        $this->loadAddresses();
    }

    public function loadAddresses(): void
    {
        $customer = Auth::guard('customer')->user();
        $this->addresses = $customer->addresses()
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();
    }

    public function showAddForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingAddressId = null;
    }

    public function editAddress(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = $customer->addresses()->findOrFail($addressId);

        $this->editingAddressId = $address->id;
        $this->first_name = $address->first_name;
        $this->last_name = $address->last_name;
        $this->address1 = $address->address1;
        $this->address2 = $address->address2 ?? '';
        $this->city = $address->city;
        $this->province = $address->province ?? '';
        $this->postal_code = $address->postal_code;
        $this->country_code = $address->country_code;
        $this->phone = $address->phone ?? '';
        $this->is_default = $address->is_default;
        $this->showForm = true;
    }

    public function saveAddress(): void
    {
        $this->validate();

        $customer = Auth::guard('customer')->user();

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'address1' => $this->address1,
            'address2' => $this->address2 ?: null,
            'city' => $this->city,
            'province' => $this->province ?: null,
            'postal_code' => $this->postal_code,
            'country_code' => $this->country_code,
            'phone' => $this->phone ?: null,
            'is_default' => $this->is_default,
        ];

        if ($this->is_default) {
            $customer->addresses()->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            $address = $customer->addresses()->findOrFail($this->editingAddressId);
            $address->update($data);
        } else {
            $data['customer_id'] = $customer->id;
            CustomerAddress::create($data);
        }

        $this->resetForm();
        $this->showForm = false;
        $this->editingAddressId = null;
        $this->loadAddresses();
    }

    public function deleteAddress(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $customer->addresses()->where('id', $addressId)->delete();
        $this->loadAddresses();
    }

    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $customer->addresses()->update(['is_default' => false]);
        $customer->addresses()->where('id', $addressId)->update(['is_default' => true]);
        $this->loadAddresses();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
        $this->editingAddressId = null;
    }

    private function resetForm(): void
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->address1 = '';
        $this->address2 = '';
        $this->city = '';
        $this->province = '';
        $this->postal_code = '';
        $this->country_code = 'DE';
        $this->phone = '';
        $this->is_default = false;
        $this->resetValidation();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.addresses.index')
            ->layout('layouts.storefront');
    }
}
