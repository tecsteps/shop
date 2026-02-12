<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('My Addresses')]
class Index extends Component
{
    public bool $showModal = false;

    public ?int $editingAddressId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $company = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $country = '';

    public string $country_code = '';

    public string $zip = '';

    public string $phone = '';

    public bool $is_default = false;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:500'],
            'address2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingAddressId = null;
        $this->showModal = true;
    }

    public function openEditModal(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = CustomerAddress::where('customer_id', $customer->id)->findOrFail($addressId);

        $this->editingAddressId = $address->id;
        $this->first_name = $address->first_name ?? '';
        $this->last_name = $address->last_name ?? '';
        $this->company = $address->company ?? '';
        $this->address1 = $address->address1 ?? '';
        $this->address2 = $address->address2 ?? '';
        $this->city = $address->city ?? '';
        $this->province = $address->province ?? '';
        $this->country = $address->country ?? '';
        $this->country_code = $address->country_code ?? '';
        $this->zip = $address->zip ?? '';
        $this->phone = $address->phone ?? '';
        $this->is_default = (bool) $address->is_default;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $customer = Auth::guard('customer')->user();

        $data = [
            'customer_id' => $customer->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company' => $this->company ?: null,
            'address1' => $this->address1,
            'address2' => $this->address2 ?: null,
            'city' => $this->city,
            'province' => $this->province ?: null,
            'country' => $this->country,
            'country_code' => $this->country_code,
            'zip' => $this->zip,
            'phone' => $this->phone ?: null,
            'is_default' => $this->is_default,
        ];

        if ($this->is_default) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            $address = CustomerAddress::where('customer_id', $customer->id)
                ->findOrFail($this->editingAddressId);
            $address->update($data);
        } else {
            // First address is default
            if ($customer->addresses()->count() === 0) {
                $data['is_default'] = true;
            }

            CustomerAddress::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = CustomerAddress::where('customer_id', $customer->id)->findOrFail($addressId);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextAddress = CustomerAddress::where('customer_id', $customer->id)->first();
            $nextAddress?->update(['is_default' => true]);
        }
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

    public function render()
    {
        $customer = Auth::guard('customer')->user();
        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $addresses,
        ]);
    }

    private function resetForm(): void
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->company = '';
        $this->address1 = '';
        $this->address2 = '';
        $this->city = '';
        $this->province = '';
        $this->country = '';
        $this->country_code = '';
        $this->zip = '';
        $this->phone = '';
        $this->is_default = false;
        $this->resetValidation();
    }
}
