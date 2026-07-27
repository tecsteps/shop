<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $company = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $province_code = '';

    public string $country = '';

    public string $country_code = '';

    public string $postal_code = '';

    public string $phone = '';

    public bool $is_default = false;

    /**
     * Open the modal with a blank form for a new address.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    /**
     * Open the modal pre-filled with an existing address (own addresses
     * only, others are a 404).
     */
    public function edit(int $addressId): void
    {
        $address = $this->findAddress($addressId);
        $data = $address->address_json ?? [];

        $this->editingId = $address->id;
        $this->label = (string) ($address->label ?? '');
        $this->first_name = (string) ($data['first_name'] ?? '');
        $this->last_name = (string) ($data['last_name'] ?? '');
        $this->company = (string) ($data['company'] ?? '');
        $this->address1 = (string) ($data['address1'] ?? '');
        $this->address2 = (string) ($data['address2'] ?? '');
        $this->city = (string) ($data['city'] ?? '');
        $this->province = (string) ($data['province'] ?? '');
        $this->province_code = (string) ($data['province_code'] ?? '');
        $this->country = (string) ($data['country'] ?? '');
        $this->country_code = (string) ($data['country_code'] ?? '');
        $this->postal_code = (string) ($data['postal_code'] ?? '');
        $this->phone = (string) ($data['phone'] ?? '');
        $this->is_default = $address->is_default;
        $this->showModal = true;
    }

    /**
     * Validate and persist the address form (create or update).
     */
    public function save(): void
    {
        $validated = $this->validate($this->rules());

        $customer = $this->customer();

        $addressJson = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'company' => $validated['company'] ?: null,
            'address1' => $validated['address1'],
            'address2' => $validated['address2'] ?: null,
            'city' => $validated['city'],
            'province' => $validated['province'] ?: null,
            'province_code' => $validated['province_code'] ?: null,
            'country' => $validated['country'],
            'country_code' => $validated['country_code'],
            'postal_code' => $validated['postal_code'],
            'phone' => $validated['phone'] ?: null,
        ];

        if ($this->editingId !== null) {
            $address = $this->findAddress($this->editingId);

            $address->update([
                'label' => $validated['label'] ?: null,
                'address_json' => $addressJson,
                'is_default' => $this->is_default,
            ]);
        } else {
            // The first saved address becomes the default automatically.
            $address = $customer->addresses()->create([
                'label' => $validated['label'] ?: null,
                'address_json' => $addressJson,
                'is_default' => $this->is_default || ! $customer->addresses()->exists(),
            ]);
        }

        if ($address->is_default) {
            $customer->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }

        $this->showModal = false;
        $this->resetForm();

        $this->dispatch('toast', type: 'success', message: 'Address saved');
    }

    /**
     * Delete an address (own addresses only).
     */
    public function delete(int $addressId): void
    {
        $this->findAddress($addressId)->delete();
    }

    /**
     * Mark an address as the default and clear the flag on the rest.
     */
    public function setDefault(int $addressId): void
    {
        $address = $this->findAddress($addressId);

        $this->customer()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    /**
     * Render the address book (spec 04 §10.6).
     */
    public function render(): View
    {
        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $this->customer()->addresses()
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get(),
        ])
            ->layout('storefront.layouts.app')
            ->title('Your addresses');
    }

    /**
     * Validation rules for the address form (address_json fields).
     *
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'label' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address1' => 'required|string|max:500',
            'address2' => 'nullable|string|max:500',
            'city' => 'required|string|max:255',
            'province' => 'nullable|string|max:255',
            'province_code' => 'nullable|string|max:10',
            'country' => 'required|string|max:255',
            'country_code' => 'required|string|size:2',
            'postal_code' => 'required|string|max:20',
            'phone' => 'nullable|string|max:50',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Find an address of the authenticated customer or 404.
     */
    private function findAddress(int $addressId): CustomerAddress
    {
        return $this->customer()->addresses()->findOrFail($addressId);
    }

    /**
     * The authenticated storefront customer.
     */
    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    /**
     * Reset the form fields back to a blank state.
     */
    private function resetForm(): void
    {
        $this->resetErrorBag();
        $this->reset([
            'label', 'first_name', 'last_name', 'company', 'address1', 'address2',
            'city', 'province', 'province_code', 'country', 'country_code',
            'postal_code', 'phone', 'is_default',
        ]);
    }
}
