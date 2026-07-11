<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public ?int $editingAddressId = null;

    public string $label = 'Home';

    public bool $isDefault = false;

    /** @var array<string, string> */
    public array $address = ['first_name' => '', 'last_name' => '', 'address1' => '', 'address2' => '', 'city' => '', 'province_code' => '', 'country_code' => 'DE', 'zip' => '', 'phone' => ''];

    public function edit(int $addressId): void
    {
        $address = $this->ownedAddress($addressId);
        $this->editingAddressId = $address->id;
        $this->label = $address->label ?? 'Home';
        $this->isDefault = $address->is_default;
        $this->address = array_merge($this->address, $address->address_json);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'isDefault' => ['boolean'],
            'address.first_name' => ['required', 'string', 'max:255'],
            'address.last_name' => ['required', 'string', 'max:255'],
            'address.address1' => ['required', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.country_code' => ['required', 'string', 'size:2'],
            'address.zip' => ['required', 'string', 'max:32'],
        ]);
        $customer = Auth::guard('customer')->user();

        if ($this->isDefault) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $model = $this->editingAddressId ? $this->ownedAddress($this->editingAddressId) : new CustomerAddress(['customer_id' => $customer->id]);
        $model->fill(['label' => $validated['label'], 'is_default' => $this->isDefault, 'address_json' => $validated['address']])->save();
        $this->resetForm();
        session()->flash('storefront_status', 'Address saved');
    }

    public function delete(int $addressId): void
    {
        $this->ownedAddress($addressId)->delete();
    }

    public function render(): View
    {
        return view('livewire.storefront.account.addresses.index', [
            'addresses' => Auth::guard('customer')->user()->addresses()->orderByDesc('is_default')->get(),
        ])->layout('layouts.storefront', ['title' => 'Addresses - '.app('current_store')->name]);
    }

    private function ownedAddress(int $addressId): CustomerAddress
    {
        return Auth::guard('customer')->user()->addresses()->findOrFail($addressId);
    }

    private function resetForm(): void
    {
        $this->editingAddressId = null;
        $this->label = 'Home';
        $this->isDefault = false;
        $this->address = ['first_name' => '', 'last_name' => '', 'address1' => '', 'address2' => '', 'city' => '', 'province_code' => '', 'country_code' => 'DE', 'zip' => '', 'phone' => ''];
    }
}
