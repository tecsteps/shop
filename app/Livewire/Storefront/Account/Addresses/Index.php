<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Livewire\Storefront\Account\CustomerComponent;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Index extends CustomerComponent
{
    public bool $formOpen = false;

    public ?int $editingId = null;

    public string $label = '';

    /** @var array<string, mixed> */
    public array $address = [
        'first_name' => '', 'last_name' => '', 'company' => '', 'address1' => '', 'address2' => '',
        'city' => '', 'province' => '', 'province_code' => '', 'postal_code' => '', 'country' => 'DE', 'phone' => '',
    ];

    public bool $isDefault = false;

    public function mount(): void
    {
        $this->authenticatedCustomer();
    }

    #[Computed]
    public function addresses(): mixed
    {
        return CustomerAddress::query()
            ->where('customer_id', $this->authenticatedCustomer()->getAuthIdentifier())
            ->orderByDesc('is_default')
            ->get();
    }

    public function createAddress(): void
    {
        $this->resetForm();
        $this->formOpen = true;
    }

    public function editAddress(int $addressId): void
    {
        $address = $this->ownedAddress($addressId);
        $data = is_string($address->address_json) ? json_decode($address->address_json, true) : (array) $address->address_json;
        $data['postal_code'] = $data['postal_code'] ?? $data['zip'] ?? '';
        $data['country'] = $data['country_code'] ?? $data['country'] ?? 'DE';

        $this->editingId = $address->getKey();
        $this->label = (string) $address->label;
        $this->address = array_replace($this->address, $data);
        $this->isDefault = (bool) $address->is_default;
        $this->formOpen = true;
    }

    public function saveAddress(): void
    {
        $this->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'address.first_name' => ['required', 'string', 'max:100'],
            'address.last_name' => ['required', 'string', 'max:100'],
            'address.company' => ['nullable', 'string', 'max:150'],
            'address.address1' => ['required', 'string', 'max:255'],
            'address.address2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:120'],
            'address.province' => ['nullable', 'string', 'max:120'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.country' => ['required', 'string', 'size:2'],
            'address.phone' => ['nullable', 'string', 'max:40'],
            'isDefault' => ['boolean'],
        ]);

        $customer = $this->authenticatedCustomer();
        DB::transaction(function () use ($customer): void {
            if ($this->isDefault || ! CustomerAddress::query()->where('customer_id', $customer->getAuthIdentifier())->exists()) {
                CustomerAddress::query()->where('customer_id', $customer->getAuthIdentifier())->update(['is_default' => false]);
                $this->isDefault = true;
            }

            $payload = [...$this->address, 'zip' => $this->address['postal_code'], 'country_code' => $this->address['country']];
            CustomerAddress::query()->updateOrCreate(
                ['id' => $this->editingId, 'customer_id' => $customer->getAuthIdentifier()],
                ['label' => $this->label ?: null, 'address_json' => $payload, 'is_default' => $this->isDefault],
            );
        });

        unset($this->addresses);
        $this->formOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Address saved');
    }

    public function deleteAddress(int $addressId): void
    {
        $address = $this->ownedAddress($addressId);
        $wasDefault = (bool) $address->is_default;
        $address->delete();

        if ($wasDefault) {
            CustomerAddress::query()->where('customer_id', $this->authenticatedCustomer()->getAuthIdentifier())->first()?->update(['is_default' => true]);
        }

        unset($this->addresses);
        $this->dispatch('toast', type: 'success', message: 'Address removed');
    }

    public function setDefault(int $addressId): void
    {
        $address = $this->ownedAddress($addressId);
        DB::transaction(function () use ($address): void {
            CustomerAddress::query()->where('customer_id', $this->authenticatedCustomer()->getAuthIdentifier())->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
        unset($this->addresses);
    }

    public function render(): View
    {
        return $this->storefront(view('storefront.account.addresses.index'), 'Your addresses - '.$this->currentStore()->name);
    }

    private function ownedAddress(int $addressId): CustomerAddress
    {
        return CustomerAddress::query()->where('customer_id', $this->authenticatedCustomer()->getAuthIdentifier())->findOrFail($addressId);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'label', 'isDefault');
        $this->resetValidation();
        $this->address = [
            'first_name' => '', 'last_name' => '', 'company' => '', 'address1' => '', 'address2' => '',
            'city' => '', 'province' => '', 'province_code' => '', 'postal_code' => '', 'country' => 'DE', 'phone' => '',
        ];
    }
}
