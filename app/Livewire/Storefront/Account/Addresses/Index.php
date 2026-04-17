<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public ?int $editingAddressId = null;

    public bool $showModal = false;

    public array $form = [
        'first_name' => '',
        'last_name' => '',
        'company' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province' => '',
        'zip' => '',
        'country' => '',
        'phone' => '',
    ];

    public string $label = '';

    public function openAddForm(): void
    {
        $this->resetForm();
        $this->editingAddressId = null;
        $this->showModal = true;
    }

    public function editAddress(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = CustomerAddress::find($addressId);

        if (! $address || $address->customer_id !== $customer->id) {
            abort(403);
        }

        $this->editingAddressId = $address->id;
        $this->label = $address->label ?? '';
        $this->form = array_merge($this->form, $address->address_json ?? []);
        $this->showModal = true;
    }

    public function saveAddress(): void
    {
        $this->validate([
            'form.first_name' => ['required', 'max:255'],
            'form.last_name' => ['required', 'max:255'],
            'form.address1' => ['required', 'max:255'],
            'form.city' => ['required', 'max:255'],
            'form.zip' => ['required', 'max:20'],
            'form.country' => ['required', 'max:2'],
        ]);

        $customer = Auth::guard('customer')->user();
        $hasNoAddresses = $customer->addresses()->count() === 0;

        $data = [
            'customer_id' => $customer->id,
            'label' => $this->label ?: null,
            'address_json' => $this->form,
            'is_default' => $hasNoAddresses && ! $this->editingAddressId,
        ];

        if ($this->editingAddressId) {
            $address = CustomerAddress::where('id', $this->editingAddressId)
                ->where('customer_id', $customer->id)
                ->firstOrFail();
            $address->update([
                'label' => $data['label'],
                'address_json' => $data['address_json'],
            ]);
        } else {
            CustomerAddress::create($data);
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function deleteAddress(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = CustomerAddress::find($addressId);

        if (! $address || $address->customer_id !== $customer->id) {
            abort(403);
        }

        $address->delete();
    }

    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = CustomerAddress::where('id', $addressId)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $customer->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    protected function resetForm(): void
    {
        $this->editingAddressId = null;
        $this->label = '';
        $this->form = [
            'first_name' => '',
            'last_name' => '',
            'company' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'province' => '',
            'zip' => '',
            'country' => '',
            'phone' => '',
        ];
    }

    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();
        $addresses = $customer->addresses()
            ->orderByDesc('is_default')
            ->get();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $addresses,
        ])->layout('layouts::storefront');
    }
}
