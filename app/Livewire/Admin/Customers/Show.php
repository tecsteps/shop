<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    use ResolvesAdminStore;

    public Customer $customer;

    public ?int $editingAddressId = null;

    public string $addressLabel = '';

    /** @var array<string, mixed> */
    public array $addressForm = [];

    public bool $showAddressModal = false;

    public function mount(Customer $customer): void
    {
        abort_if($customer->store_id !== $this->store()->getKey(), 404);

        Gate::authorize('view', $customer);

        $this->customer = $customer;
    }

    public function openAddressForm(?int $addressId = null): void
    {
        Gate::authorize('update', $this->customer);

        $this->editingAddressId = $addressId;

        if ($addressId !== null) {
            $address = $this->customer->customerAddresses()->whereKey($addressId)->firstOrFail();
            $this->addressLabel = (string) $address->label;
            $this->addressForm = array_merge($this->emptyAddress(), is_array($address->address_json) ? $address->address_json : []);
        } else {
            $this->addressLabel = '';
            $this->addressForm = $this->emptyAddress();
        }

        $this->showAddressModal = true;
    }

    public function saveAddress(): void
    {
        Gate::authorize('update', $this->customer);

        $validated = $this->validate([
            'addressLabel' => 'required|string|max:50',
            'addressForm.first_name' => 'required|string|max:100',
            'addressForm.last_name' => 'required|string|max:100',
            'addressForm.address1' => 'required|string|max:255',
            'addressForm.city' => 'required|string|max:100',
            'addressForm.zip' => 'required|string|max:20',
            'addressForm.country_code' => 'required|string|size:2',
        ]);

        $payload = array_merge($this->emptyAddress(), $this->addressForm);

        if ($this->editingAddressId !== null) {
            $address = $this->customer->customerAddresses()->whereKey($this->editingAddressId)->firstOrFail();
            $address->update(['label' => $validated['addressLabel'], 'address_json' => $payload]);
        } else {
            $this->customer->customerAddresses()->create([
                'label' => $validated['addressLabel'],
                'address_json' => $payload,
                'is_default' => ! $this->customer->customerAddresses()->exists(),
            ]);
        }

        $this->showAddressModal = false;
        $this->toast('success', 'Address saved.');
    }

    public function deleteAddress(int $addressId): void
    {
        Gate::authorize('update', $this->customer);

        $address = $this->customer->customerAddresses()->whereKey($addressId)->firstOrFail();
        $wasDefault = (bool) $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $first = $this->customer->customerAddresses()->orderBy('id')->first();

            if ($first !== null) {
                $first->update(['is_default' => true]);
            }
        }

        $this->toast('success', 'Address deleted.');
    }

    public function setDefaultAddress(int $addressId): void
    {
        Gate::authorize('update', $this->customer);

        $this->customer->customerAddresses()->update(['is_default' => false]);
        $this->customer->customerAddresses()->whereKey($addressId)->update(['is_default' => true]);

        $this->toast('success', 'Default address updated.');
    }

    public function render()
    {
        $customer = Customer::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($this->customer->getKey())
            ->with(['customerAddresses' => fn ($query) => $query->orderByDesc('is_default')->orderBy('id')])
            ->firstOrFail();

        $orders = $customer->orders()->orderByDesc('placed_at')->paginate(10);

        return view('livewire.admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
        ])->layout('livewire.admin.layout.app', ['title' => $customer->name]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyAddress(): array
    {
        return [
            'first_name' => '',
            'last_name' => '',
            'company' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'province' => '',
            'province_code' => '',
            'country' => '',
            'country_code' => '',
            'zip' => '',
            'phone' => '',
        ];
    }
}
