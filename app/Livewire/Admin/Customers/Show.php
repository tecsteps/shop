<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Customer Detail')]
class Show extends Component
{
    public Customer $customer;

    /** @var array{label: string, line1: string, line2: string, city: string, state: string, zip: string, country: string} */
    public array $addressForm = [
        'label' => '',
        'line1' => '',
        'line2' => '',
        'city' => '',
        'state' => '',
        'zip' => '',
        'country' => '',
    ];

    public ?int $editingAddressId = null;

    public function mount(Customer $customer): void
    {
        $this->authorize('view', $customer);
        $this->customer = $customer->load(['orders', 'addresses']);
    }

    public function openAddressForm(?int $addressId = null): void
    {
        $this->editingAddressId = $addressId;

        if ($addressId) {
            $address = $this->customer->addresses()->findOrFail($addressId);
            $this->addressForm = [
                'label' => $address->label ?? '',
                'line1' => $address->address_json['line1'] ?? '',
                'line2' => $address->address_json['line2'] ?? '',
                'city' => $address->address_json['city'] ?? '',
                'state' => $address->address_json['state'] ?? '',
                'zip' => $address->address_json['zip'] ?? '',
                'country' => $address->address_json['country'] ?? '',
            ];
        } else {
            $this->addressForm = ['label' => '', 'line1' => '', 'line2' => '', 'city' => '', 'state' => '', 'zip' => '', 'country' => ''];
        }

        $this->modal('address-form')->show();
    }

    public function saveAddress(): void
    {
        $data = [
            'label' => $this->addressForm['label'],
            'address_json' => [
                'line1' => $this->addressForm['line1'],
                'line2' => $this->addressForm['line2'],
                'city' => $this->addressForm['city'],
                'state' => $this->addressForm['state'],
                'zip' => $this->addressForm['zip'],
                'country' => $this->addressForm['country'],
            ],
        ];

        if ($this->editingAddressId) {
            $this->customer->addresses()->where('id', $this->editingAddressId)->update($data);
        } else {
            $this->customer->addresses()->create($data);
        }

        $this->customer->refresh()->load('addresses');
        $this->dispatch('toast', type: 'success', message: 'Address saved.');
        $this->modal('address-form')->close();
    }

    public function deleteAddress(int $addressId): void
    {
        $this->customer->addresses()
            ->where('id', $addressId)
            ->delete();

        $this->customer->refresh()->load('addresses');
        $this->dispatch('toast', type: 'success', message: 'Address deleted.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.customers.show');
    }
}
