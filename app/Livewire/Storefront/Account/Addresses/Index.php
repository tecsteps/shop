<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Contracts\View\View;
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

    public string $address1 = '';

    public string $city = '';

    public string $provinceCode = '';

    public string $countryCode = 'US';

    public string $postalCode = '';

    public bool $isDefault = false;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'provinceCode' => ['required', 'string', 'max:10'],
            'countryCode' => ['required', 'string', 'max:10'],
            'postalCode' => ['required', 'string', 'max:20'],
        ];
    }

    public function openForm(?int $addressId = null): void
    {
        $this->resetForm();

        if ($addressId) {
            $address = $this->getCustomerAddress($addressId);
            if ($address) {
                $this->editingAddressId = $address->id;
                $this->label = $address->label ?? '';
                $this->firstName = $address->address_json['first_name'] ?? '';
                $this->lastName = $address->address_json['last_name'] ?? '';
                $this->address1 = $address->address_json['address1'] ?? '';
                $this->city = $address->address_json['city'] ?? '';
                $this->provinceCode = $address->address_json['province_code'] ?? '';
                $this->countryCode = $address->address_json['country_code'] ?? 'US';
                $this->postalCode = $address->address_json['postal_code'] ?? '';
                $this->isDefault = (bool) $address->is_default;
            }
        }

        $this->showForm = true;
    }

    public function saveAddress(): void
    {
        $this->validate();

        $customer = Auth::guard('customer')->user();

        $addressData = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address1' => $this->address1,
            'city' => $this->city,
            'province_code' => $this->provinceCode,
            'country_code' => $this->countryCode,
            'postal_code' => $this->postalCode,
        ];

        if ($this->isDefault) {
            $customer->addresses()->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            $address = $this->getCustomerAddress($this->editingAddressId);
            if ($address) {
                $address->update([
                    'label' => $this->label,
                    'address_json' => $addressData,
                    'is_default' => $this->isDefault,
                ]);
            }
        } else {
            $customer->addresses()->create([
                'label' => $this->label,
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

        if ($address) {
            $customer->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        }
    }

    public function render(): View
    {
        $customer = Auth::guard('customer')->user();
        $addresses = $customer->addresses()->get();

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
        $this->address1 = '';
        $this->city = '';
        $this->provinceCode = '';
        $this->countryCode = 'US';
        $this->postalCode = '';
        $this->isDefault = false;
        $this->resetValidation();
    }

    private function getCustomerAddress(int $addressId): ?CustomerAddress
    {
        $customer = Auth::guard('customer')->user();

        return CustomerAddress::query()
            ->where('id', $addressId)
            ->where('customer_id', $customer->id)
            ->first();
    }
}
