<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingAddressId = null;

    public string $label = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $company = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $country = 'DE';

    public string $zip = '';

    public string $phone = '';

    public bool $isDefault = false;

    public function openAddForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editAddress(int $addressId): void
    {
        $address = $this->findAddress($addressId);
        if (! $address) {
            return;
        }

        $this->editingAddressId = $address->id;
        $this->label = $address->label ?? '';
        $this->firstName = $address->address_json['first_name'] ?? '';
        $this->lastName = $address->address_json['last_name'] ?? '';
        $this->company = $address->address_json['company'] ?? '';
        $this->address1 = $address->address_json['address1'] ?? '';
        $this->address2 = $address->address_json['address2'] ?? '';
        $this->city = $address->address_json['city'] ?? '';
        $this->province = $address->address_json['province'] ?? '';
        $this->country = $address->address_json['country'] ?? 'DE';
        $this->zip = $address->address_json['zip'] ?? '';
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
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:2'],
            'zip' => ['required', 'string', 'max:20'],
        ]);

        $customer = Auth::guard('customer')->user();

        $addressData = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'company' => $this->company ?: null,
            'address1' => $this->address1,
            'address2' => $this->address2 ?: null,
            'city' => $this->city,
            'province' => $this->province ?: null,
            'province_code' => null,
            'country' => $this->country,
            'zip' => $this->zip,
            'phone' => $this->phone ?: null,
        ];

        if ($this->isDefault) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            $address = $this->findAddress($this->editingAddressId);
            if ($address) {
                $address->update([
                    'label' => $this->label ?: null,
                    'address_json' => $addressData,
                    'is_default' => $this->isDefault,
                ]);
            }
        } else {
            $isFirst = CustomerAddress::where('customer_id', $customer->id)->count() === 0;

            CustomerAddress::create([
                'customer_id' => $customer->id,
                'label' => $this->label ?: null,
                'address_json' => $addressData,
                'is_default' => $this->isDefault || $isFirst,
            ]);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function deleteAddress(int $addressId): void
    {
        $address = $this->findAddress($addressId);
        if ($address) {
            $address->delete();
        }
    }

    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();

        CustomerAddress::where('customer_id', $customer->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $address = $this->findAddress($addressId);
        if ($address) {
            $address->update(['is_default' => true]);
        }
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingAddressId = null;
        $this->label = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->company = '';
        $this->address1 = '';
        $this->address2 = '';
        $this->city = '';
        $this->province = '';
        $this->country = 'DE';
        $this->zip = '';
        $this->phone = '';
        $this->isDefault = false;
    }

    protected function findAddress(int $addressId): ?CustomerAddress
    {
        $customer = Auth::guard('customer')->user();

        return CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $addressId)
            ->first();
    }

    public function render(): \Illuminate\View\View
    {
        $customer = Auth::guard('customer')->user();

        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->get();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $addresses,
        ]);
    }
}
