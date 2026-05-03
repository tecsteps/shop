<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingAddressId = null;

    public string $label = '';

    /**
     * @var array<string, string|null>
     */
    public array $address = [
        'first_name' => '',
        'last_name' => '',
        'company' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province' => '',
        'province_code' => '',
        'country' => 'Germany',
        'country_code' => 'DE',
        'postal_code' => '',
        'phone' => '',
    ];

    public bool $isDefault = false;

    public function startCreating(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEditing(int $addressId): void
    {
        $address = $this->addressQuery()->whereKey($addressId)->firstOrFail();

        $this->editingAddressId = $address->id;
        $this->label = $address->label ?? '';
        $this->address = array_merge($this->address, $address->address_json ?? []);
        $this->isDefault = $address->is_default;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'address.first_name' => ['required', 'string', 'max:255'],
            'address.last_name' => ['required', 'string', 'max:255'],
            'address.address1' => ['required', 'string', 'max:500'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.country_code' => ['required', 'string', 'size:2'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'isDefault' => ['bool'],
        ]);

        $customer = $this->customer();
        $address = $this->editingAddressId === null
            ? new CustomerAddress(['customer_id' => $customer->id])
            : $this->addressQuery()->whereKey($this->editingAddressId)->firstOrFail();

        $address->forceFill([
            'label' => $this->label !== '' ? $this->label : null,
            'address_json' => $this->address,
            'is_default' => $this->isDefault || ! $customer->addresses()->exists(),
        ])->save();

        if ($address->is_default) {
            $this->clearOtherDefaults($address);
        }

        $this->resetForm();
    }

    public function setDefault(int $addressId): void
    {
        $address = $this->addressQuery()->whereKey($addressId)->firstOrFail();
        $address->forceFill(['is_default' => true])->save();
        $this->clearOtherDefaults($address);
    }

    public function deleteAddress(int $addressId): void
    {
        $address = $this->addressQuery()->whereKey($addressId)->firstOrFail();
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $this->addressQuery()->oldest('id')->first()?->forceFill(['is_default' => true])->save();
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $this->addressQuery()->orderByDesc('is_default')->oldest('id')->get(),
        ])->layout('storefront.layouts.app', [
            'title' => 'Addresses',
        ]);
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingAddressId = null;
        $this->label = '';
        $this->address = [
            'first_name' => '',
            'last_name' => '',
            'company' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '',
            'phone' => '',
        ];
        $this->isDefault = false;
    }

    private function clearOtherDefaults(CustomerAddress $defaultAddress): void
    {
        $this->addressQuery()
            ->whereKeyNot($defaultAddress->id)
            ->update(['is_default' => false]);
    }

    /**
     * @return Builder<CustomerAddress>
     */
    private function addressQuery(): Builder
    {
        return CustomerAddress::query()->where('customer_id', $this->customer()->id);
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }
}
