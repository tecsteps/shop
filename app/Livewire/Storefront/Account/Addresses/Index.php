<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $company = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $country = 'DE';

    public string $zip = '';

    public string $phone = '';

    public bool $is_default = false;

    public function openForm(?int $addressId = null): void
    {
        if ($addressId) {
            $address = CustomerAddress::query()->findOrFail($addressId);
            $this->editingId = $addressId;
            $this->first_name = $address->first_name ?? '';
            $this->last_name = $address->last_name ?? '';
            $this->company = $address->company ?? '';
            $this->address1 = $address->address1 ?? '';
            $this->address2 = $address->address2 ?? '';
            $this->city = $address->city ?? '';
            $this->province = $address->province ?? '';
            $this->country = $address->country ?? '';
            $this->zip = $address->zip ?? '';
            $this->phone = $address->phone ?? '';
            $this->is_default = $address->is_default;
        } else {
            $this->resetForm();
        }
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'address1' => ['required', 'string'],
            'city' => ['required', 'string'],
            'country' => ['required', 'string'],
            'zip' => ['required', 'string'],
        ]);

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
            'zip' => $this->zip,
            'phone' => $this->phone ?: null,
            'is_default' => $this->is_default,
        ];

        if ($this->is_default) {
            CustomerAddress::query()->where('customer_id', $customer->id)->update(['is_default' => false]);
        }

        if ($this->editingId) {
            CustomerAddress::query()->where('id', $this->editingId)->update($data);
        } else {
            CustomerAddress::query()->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        CustomerAddress::query()->where('customer_id', $customer->id)->update(['is_default' => false]);
        CustomerAddress::query()->where('id', $addressId)->update(['is_default' => true]);
    }

    public function delete(int $addressId): void
    {
        CustomerAddress::query()->where('id', $addressId)->delete();
    }

    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $customer->addresses()->orderByDesc('is_default')->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->company = '';
        $this->address1 = '';
        $this->address2 = '';
        $this->city = '';
        $this->province = '';
        $this->country = 'DE';
        $this->zip = '';
        $this->phone = '';
        $this->is_default = false;
    }
}
