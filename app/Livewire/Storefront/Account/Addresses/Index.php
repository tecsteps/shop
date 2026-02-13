<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingAddressId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $company = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province_code = '';

    public string $country_code = 'US';

    public string $postal_code = '';

    public string $phone = '';

    public bool $is_default = false;

    /**
     * Open the form for creating a new address.
     */
    public function createAddress(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Open the form for editing an existing address.
     */
    public function editAddress(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();

        $address = CustomerAddress::where('id', $addressId)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $this->editingAddressId = $address->id;
        $this->first_name = $address->first_name;
        $this->last_name = $address->last_name;
        $this->company = $address->company ?? '';
        $this->address1 = $address->address1;
        $this->address2 = $address->address2 ?? '';
        $this->city = $address->city;
        $this->province_code = $address->province_code ?? '';
        $this->country_code = $address->country_code;
        $this->postal_code = $address->postal_code;
        $this->phone = $address->phone ?? '';
        $this->is_default = $address->is_default;
        $this->showForm = true;
    }

    /**
     * Save the address (create or update).
     */
    public function saveAddress(): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province_code' => ['nullable', 'string', 'max:10'],
            'country_code' => ['required', 'string', 'size:2'],
            'postal_code' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_default' => ['boolean'],
        ]);

        $customer = Auth::guard('customer')->user();

        if ($validated['is_default']) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            $address = CustomerAddress::where('id', $this->editingAddressId)
                ->where('customer_id', $customer->id)
                ->firstOrFail();

            $address->update($validated);
        } else {
            $validated['customer_id'] = $customer->id;
            CustomerAddress::create($validated);
        }

        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Delete an address.
     */
    public function deleteAddress(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();

        CustomerAddress::where('id', $addressId)
            ->where('customer_id', $customer->id)
            ->delete();
    }

    /**
     * Set an address as the default.
     */
    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();

        CustomerAddress::where('customer_id', $customer->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        CustomerAddress::where('id', $addressId)
            ->where('customer_id', $customer->id)
            ->update(['is_default' => true]);
    }

    /**
     * Cancel form and reset state.
     */
    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Reset form fields.
     */
    protected function resetForm(): void
    {
        $this->editingAddressId = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->company = '';
        $this->address1 = '';
        $this->address2 = '';
        $this->city = '';
        $this->province_code = '';
        $this->country_code = 'US';
        $this->postal_code = '';
        $this->phone = '';
        $this->is_default = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        $customer = Auth::guard('customer')->user();

        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $addresses,
        ])->layout('storefront.layouts.app', [
            'title' => 'Addresses',
        ]);
    }
}
