<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public bool $showAddressForm = false;

    public ?int $editingAddressId = null;

    /** @var array<string, string> */
    public array $editingAddress = [];

    public ?int $deleteAddressId = null;

    public bool $isDefault = false;

    public string $label = '';

    /** @var array<string, string> */
    public array $address = [
        'first_name' => '', 'last_name' => '', 'address_line_1' => '', 'address_line_2' => '', 'city' => '',
        'state' => '', 'country' => 'DE', 'postal_code' => '', 'phone' => '',
    ];

    public function openAddressForm(): void
    {
        $this->resetForm();
        $this->showAddressForm = true;
    }

    public function closeAddressForm(): void
    {
        $this->resetForm();
        $this->showAddressForm = false;
    }

    public function editAddress(int $addressId): void
    {
        $saved = $this->customer()->addresses()->findOrFail($addressId);
        $this->editingAddressId = $saved->id;
        $this->label = $saved->label ?? '';
        $this->address = $this->normalizeAddress($saved->address_json ?? []);
        $this->editingAddress = $this->address;
        $this->isDefault = $saved->is_default;
        $this->showAddressForm = true;
    }

    public function saveAddress(): void
    {
        $this->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'address.first_name' => ['required', 'string', 'max:100'],
            'address.last_name' => ['required', 'string', 'max:100'],
            'address.address_line_1' => ['required', 'string', 'max:255'],
            'address.address_line_2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.state' => ['nullable', 'string', 'max:100'],
            'address.country' => ['required', 'string', 'size:2'],
            'address.postal_code' => ['required', 'string', 'max:24'],
            'address.phone' => ['nullable', 'string', 'max:32'],
            'isDefault' => ['boolean'],
        ]);

        $customer = $this->customer();

        DB::transaction(function () use ($customer): void {
            if ($this->isDefault) {
                $customer->addresses()->update(['is_default' => false]);
            }

            $saved = $this->editingAddressId
                ? $customer->addresses()->findOrFail($this->editingAddressId)
                : new CustomerAddress;
            $normalized = $this->normalizeAddress($this->address);
            $saved->fill([
                'label' => $this->label,
                'address_json' => $normalized,
                'is_default' => $this->isDefault || ! $customer->addresses()->exists(),
            ]);
            $customer->addresses()->save($saved);
        });

        $this->closeAddressForm();
        session()->flash('status', 'Address saved.');
    }

    public function confirmDelete(int $addressId): void
    {
        $this->customer()->addresses()->findOrFail($addressId);
        $this->deleteAddressId = $addressId;
    }

    public function cancelDelete(): void
    {
        $this->deleteAddressId = null;
    }

    public function deleteAddress(): void
    {
        $customer = $this->customer();
        $address = $customer->addresses()->findOrFail($this->deleteAddressId);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault && $nextDefault = $customer->addresses()->oldest('id')->first()) {
            $nextDefault->forceFill(['is_default' => true])->save();
        }

        $this->deleteAddressId = null;
        session()->flash('status', 'Address removed.');
    }

    public function setDefault(int $addressId): void
    {
        $customer = $this->customer();
        $customer->addresses()->update(['is_default' => false]);
        $customer->addresses()->findOrFail($addressId)->forceFill(['is_default' => true])->save();
        session()->flash('status', 'Default address updated.');
    }

    public function render(): mixed
    {
        $addresses = $this->customer()->addresses()->orderByDesc('is_default')->orderBy('id')->get();

        $addresses->each(function (CustomerAddress $address): void {
            $address->setAttribute('address_json', $this->normalizeAddress($address->address_json ?? []));
        });

        return view('storefront.account.addresses', compact('addresses'))->layout('storefront.layouts.app');
    }

    private function customer(): mixed
    {
        return auth('customer')->user();
    }

    /** @param array<string, mixed> $address
     * @return array<string, string>
     */
    private function normalizeAddress(array $address): array
    {
        $country = strtoupper((string) ($address['country'] ?? $address['country_code'] ?? 'DE'));
        $addressLine1 = (string) ($address['address_line_1'] ?? $address['address1'] ?? '');
        $addressLine2 = (string) ($address['address_line_2'] ?? $address['address2'] ?? '');
        $postalCode = (string) ($address['postal_code'] ?? $address['zip'] ?? '');
        $state = (string) ($address['state'] ?? $address['province'] ?? '');

        return [
            'first_name' => (string) ($address['first_name'] ?? ''),
            'last_name' => (string) ($address['last_name'] ?? ''),
            'address_line_1' => $addressLine1,
            'address_line_2' => $addressLine2,
            'address1' => $addressLine1,
            'address2' => $addressLine2,
            'city' => (string) ($address['city'] ?? ''),
            'state' => $state,
            'province' => $state,
            'country' => $country,
            'country_code' => $country,
            'postal_code' => $postalCode,
            'zip' => $postalCode,
            'phone' => (string) ($address['phone'] ?? ''),
        ];
    }

    private function resetForm(): void
    {
        $this->reset(['editingAddressId', 'editingAddress', 'label', 'isDefault']);
        $this->address = [
            'first_name' => '', 'last_name' => '', 'address_line_1' => '', 'address_line_2' => '', 'city' => '',
            'state' => '', 'country' => 'DE', 'postal_code' => '', 'phone' => '',
        ];
    }
}
