<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingAddressId = null;

    public string $label = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $company = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $country = 'US';

    public string $zip = '';

    public string $phone = '';

    public bool $is_default = false;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:2'],
            'zip' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_default' => ['boolean'],
        ];
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editAddress(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = CustomerAddress::where('customer_id', $customer->id)
            ->findOrFail($addressId);

        $this->editingAddressId = $address->id;
        $this->label = $address->label ?? '';
        $this->first_name = $address->address_json['first_name'] ?? '';
        $this->last_name = $address->address_json['last_name'] ?? '';
        $this->company = $address->address_json['company'] ?? '';
        $this->address1 = $address->address_json['address1'] ?? '';
        $this->address2 = $address->address_json['address2'] ?? '';
        $this->city = $address->address_json['city'] ?? '';
        $this->province = $address->address_json['province'] ?? '';
        $this->country = $address->address_json['country_code'] ?? 'US';
        $this->zip = $address->address_json['zip'] ?? '';
        $this->phone = $address->address_json['phone'] ?? '';
        $this->is_default = $address->is_default;
        $this->showForm = true;
    }

    public function saveAddress(): void
    {
        $this->validate();

        $customer = Auth::guard('customer')->user();

        $addressJson = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company' => $this->company ?: null,
            'address1' => $this->address1,
            'address2' => $this->address2 ?: null,
            'city' => $this->city,
            'province' => $this->province ?: null,
            'country_code' => $this->country,
            'zip' => $this->zip,
            'phone' => $this->phone ?: null,
        ];

        if ($this->is_default) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            $address = CustomerAddress::where('customer_id', $customer->id)
                ->findOrFail($this->editingAddressId);

            $address->update([
                'label' => $this->label,
                'address_json' => $addressJson,
                'is_default' => $this->is_default,
            ]);
        } else {
            CustomerAddress::create([
                'customer_id' => $customer->id,
                'label' => $this->label,
                'address_json' => $addressJson,
                'is_default' => $this->is_default,
            ]);
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function deleteAddress(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();

        CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $addressId)
            ->delete();
    }

    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();

        CustomerAddress::where('customer_id', $customer->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $addressId)
            ->update(['is_default' => true]);
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();

        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->get();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $addresses,
        ])->layout('layouts.storefront.app', [
            'title' => 'Addresses',
        ]);
    }

    protected function resetForm(): void
    {
        $this->editingAddressId = null;
        $this->label = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->company = '';
        $this->address1 = '';
        $this->address2 = '';
        $this->city = '';
        $this->province = '';
        $this->country = 'US';
        $this->zip = '';
        $this->phone = '';
        $this->is_default = false;
        $this->resetValidation();
    }
}
