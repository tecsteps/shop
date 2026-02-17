<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
#[Title('Address Book')]
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

    public string $countryCode = '';

    public string $zip = '';

    public string $phone = '';

    public bool $isDefault = false;

    public function openNewForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editAddress(int $addressId): void
    {
        $address = $this->getCustomerAddress($addressId);
        if (! $address) {
            return;
        }

        $this->editingAddressId = $address->id;
        $this->label = $address->label ?? '';
        $data = $address->address_json ?? [];
        $this->firstName = $data['first_name'] ?? '';
        $this->lastName = $data['last_name'] ?? '';
        $this->company = $data['company'] ?? '';
        $this->address1 = $data['address1'] ?? '';
        $this->address2 = $data['address2'] ?? '';
        $this->city = $data['city'] ?? '';
        $this->province = $data['province'] ?? '';
        $this->countryCode = $data['country_code'] ?? '';
        $this->zip = $data['zip'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->isDefault = (bool) $address->is_default;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'address1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'countryCode' => 'required|string|max:10',
            'zip' => 'required|string|max:20',
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
            'country' => $this->countryCode,
            'country_code' => $this->countryCode,
            'zip' => $this->zip,
            'phone' => $this->phone ?: null,
        ];

        if ($this->isDefault) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            $address = $this->getCustomerAddress($this->editingAddressId);
            if ($address) {
                $address->update([
                    'label' => $this->label ?: null,
                    'address_json' => $addressData,
                    'is_default' => $this->isDefault,
                ]);
            }
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
        $address = $this->getCustomerAddress($addressId);
        if ($address) {
            $address->delete();
        }
    }

    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = $this->getCustomerAddress($addressId);

        if (! $address) {
            return;
        }

        CustomerAddress::where('customer_id', $customer->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
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

    private function resetForm(): void
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
        $this->countryCode = '';
        $this->zip = '';
        $this->phone = '';
        $this->isDefault = false;
    }

    private function getCustomerAddress(int $addressId): ?CustomerAddress
    {
        $customer = Auth::guard('customer')->user();

        return CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $addressId)
            ->first();
    }
}
