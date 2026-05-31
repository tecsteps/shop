<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Customer detail: profile, order history, and saved addresses with an
 * add/edit modal.
 */
#[Layout('livewire.admin.layout.app')]
class Show extends Component
{
    use BindsCurrentStore;

    public Customer $customer;

    public bool $showAddressModal = false;

    public ?int $editingAddressId = null;

    public string $addressLabel = '';

    /** @var array<string, string> */
    public array $addressJson = [
        'first_name' => '', 'last_name' => '', 'address1' => '', 'address2' => '',
        'city' => '', 'province_code' => '', 'postal_code' => '', 'country' => '',
    ];

    public function mount(Customer $customer): void
    {
        $this->authorize('view', $customer);
        $this->customer = $customer->load(['addresses']);
    }

    public function getOrdersProperty()
    {
        return $this->customer->orders()->orderByDesc('placed_at')->paginate(10);
    }

    public function openAddressForm(?int $addressId = null): void
    {
        $this->resetValidation();
        $this->editingAddressId = $addressId;

        if ($addressId !== null) {
            $address = $this->customer->addresses()->findOrFail($addressId);
            $this->addressLabel = (string) $address->label;
            $this->addressJson = array_merge($this->addressJson, $address->address_json ?? []);
        } else {
            $this->reset('addressLabel', 'addressJson');
            $this->addressJson = [
                'first_name' => '', 'last_name' => '', 'address1' => '', 'address2' => '',
                'city' => '', 'province_code' => '', 'postal_code' => '', 'country' => '',
            ];
        }

        $this->showAddressModal = true;
    }

    public function saveAddress(): void
    {
        $this->authorize('update', $this->customer);

        $this->validate([
            'addressLabel' => ['required', 'string', 'max:255'],
            'addressJson.address1' => ['required', 'string', 'max:255'],
            'addressJson.city' => ['required', 'string', 'max:255'],
            'addressJson.country' => ['required', 'string', 'max:255'],
        ]);

        $data = ['label' => $this->addressLabel, 'address_json' => $this->addressJson];

        if ($this->editingAddressId !== null) {
            $this->customer->addresses()->whereKey($this->editingAddressId)->update($data);
        } else {
            $data['is_default'] = $this->customer->addresses()->count() === 0;
            $this->customer->addresses()->create($data);
        }

        $this->showAddressModal = false;
        $this->customer->load('addresses');
        $this->dispatch('toast', type: 'success', message: __('Customer saved'));
    }

    public function deleteAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer);
        $this->customer->addresses()->whereKey($addressId)->delete();
        $this->customer->load('addresses');
        $this->dispatch('toast', type: 'success', message: __('Address removed'));
    }

    public function setDefaultAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer);
        $this->customer->addresses()->update(['is_default' => false]);
        $this->customer->addresses()->whereKey($addressId)->update(['is_default' => true]);
        $this->customer->load('addresses');
        $this->dispatch('toast', type: 'success', message: __('Default address updated'));
    }

    public function render()
    {
        return view('livewire.admin.customers.show');
    }
}
