<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Show extends Component
{
    use WithPagination;

    public Customer $customer;

    public ?CustomerAddress $editingAddress = null;

    public string $addressLabel = '';

    /** @var array{line1: string, line2: string, city: string, state: string, zip: string, country: string} */
    public array $addressJson = [
        'line1' => '',
        'line2' => '',
        'city' => '',
        'state' => '',
        'zip' => '',
        'country' => '',
    ];

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load('addresses');
    }

    public function openAddressForm(?CustomerAddress $address = null): void
    {
        $this->editingAddress = $address;

        if ($address) {
            $this->addressLabel = $address->label ?? '';
            $this->addressJson = $address->address_json ?? $this->addressJson;
        } else {
            $this->addressLabel = '';
            $this->addressJson = [
                'line1' => '',
                'line2' => '',
                'city' => '',
                'state' => '',
                'zip' => '',
                'country' => '',
            ];
        }

        $this->modal('address-form')->show();
    }

    public function saveAddress(): void
    {
        if ($this->editingAddress) {
            $this->editingAddress->update([
                'label' => $this->addressLabel,
                'address_json' => $this->addressJson,
            ]);
        } else {
            $this->customer->addresses()->create([
                'label' => $this->addressLabel,
                'address_json' => $this->addressJson,
                'is_default' => $this->customer->addresses()->count() === 0,
            ]);
        }

        $this->customer->refresh();
        $this->modal('address-form')->close();
        $this->dispatch('toast', type: 'success', message: 'Address saved.');
    }

    public function deleteAddress(int $addressId): void
    {
        CustomerAddress::findOrFail($addressId)->delete();
        $this->customer->refresh();
        $this->dispatch('toast', type: 'success', message: 'Address deleted.');
    }

    public function setDefaultAddress(int $addressId): void
    {
        $this->customer->addresses()->update(['is_default' => false]);
        CustomerAddress::findOrFail($addressId)->update(['is_default' => true]);
        $this->customer->refresh();
        $this->dispatch('toast', type: 'success', message: 'Default address updated.');
    }

    public function getOrdersProperty()
    {
        return $this->customer->orders()->orderByDesc('placed_at')->paginate(10);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.customers.show', [
            'orders' => $this->orders,
        ]);
    }
}
