<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Customer $customer;

    public ?CustomerAddress $editingAddress = null;

    public bool $showAddressModal = false;

    public bool $editingCustomer = false;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public bool $marketingOptIn = false;

    public string $addressLabel = '';

    /** @var array<string, string> */
    public array $addressJson = [
        'line1' => '',
        'line2' => '',
        'city' => '',
        'state' => '',
        'zip' => '',
        'country' => 'DE',
    ];

    public string $message = '';

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
        $this->authorize('view', $this->customer);
        $this->loadCustomer();
        $this->fillCustomerForm();
    }

    public function openCustomerForm(): void
    {
        $this->authorize('update', $this->customer);
        $this->fillCustomerForm();
        $this->resetValidation();
        $this->editingCustomer = true;
    }

    public function saveCustomer(): void
    {
        $this->authorize('update', $this->customer);
        $data = $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->where(fn ($query) => $query->where('store_id', app('current_store')->getKey()))->ignore($this->customer->id)],
        ]);

        $this->customer->update([
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'metadata' => array_merge($this->customer->metadata ?? [], ['marketing_opt_in' => $this->marketingOptIn]),
        ]);
        $this->editingCustomer = false;
        $this->message = 'Customer details saved.';
        $this->loadCustomer();
    }

    public function openAddressForm(?int $addressId = null): void
    {
        $this->authorize('update', $this->customer);
        $this->resetValidation();
        $this->editingAddress = $addressId === null
            ? null
            : $this->customer->addresses()->whereKey($addressId)->firstOrFail();
        $this->addressLabel = (string) ($this->editingAddress?->label ?? '');
        $stored = $this->editingAddress?->address_json ?? [];
        $this->addressJson = [
            'line1' => (string) ($stored['line1'] ?? $stored['address1'] ?? ''),
            'line2' => (string) ($stored['line2'] ?? $stored['address2'] ?? ''),
            'city' => (string) ($stored['city'] ?? ''),
            'state' => (string) ($stored['state'] ?? $stored['province'] ?? ''),
            'zip' => (string) ($stored['zip'] ?? $stored['postal_code'] ?? ''),
            'country' => strtoupper((string) ($stored['country'] ?? $stored['country_code'] ?? 'DE')),
        ];
        $this->showAddressModal = true;
    }

    public function saveAddress(): void
    {
        $this->authorize('update', $this->customer);
        $data = $this->validate([
            'addressLabel' => ['nullable', 'string', 'max:100'],
            'addressJson.line1' => ['required', 'string', 'max:500'],
            'addressJson.line2' => ['nullable', 'string', 'max:500'],
            'addressJson.city' => ['required', 'string', 'max:255'],
            'addressJson.state' => ['nullable', 'string', 'max:255'],
            'addressJson.zip' => ['required', 'string', 'max:30'],
            'addressJson.country' => ['required', 'string', 'size:2'],
        ]);
        $addressData = [
            'label' => $data['addressLabel'] ?: null,
            'address_json' => array_map('trim', $data['addressJson']),
        ];

        if ($this->editingAddress === null) {
            $this->customer->addresses()->create($addressData + ['is_default' => ! $this->customer->addresses()->exists()]);
        } else {
            $this->editingAddress->update($addressData);
        }

        $this->showAddressModal = false;
        $this->message = 'Address saved.';
        $this->loadCustomer();
    }

    public function deleteAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer);
        $address = $this->customer->addresses()->whereKey($addressId)->firstOrFail();
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $this->customer->addresses()->latest('id')->first()?->update(['is_default' => true]);
        }

        $this->message = 'Address deleted.';
        $this->loadCustomer();
    }

    public function setDefaultAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer);
        $address = $this->customer->addresses()->whereKey($addressId)->firstOrFail();
        $this->customer->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        $this->message = 'Default address updated.';
        $this->loadCustomer();
    }

    public function render(): mixed
    {
        $orders = $this->customer->orders()
            ->select(['id', 'customer_id', 'order_number', 'status', 'financial_status', 'total_amount', 'currency', 'placed_at'])
            ->latest('placed_at')
            ->paginate(8, pageName: 'customer-orders');

        return view('livewire.admin.customers.show', compact('orders'))->layout('layouts.admin');
    }

    private function loadCustomer(): void
    {
        $this->customer = $this->customer->refresh()->load('addresses');
    }

    private function fillCustomerForm(): void
    {
        $this->firstName = (string) $this->customer->first_name;
        $this->lastName = (string) $this->customer->last_name;
        $this->email = (string) $this->customer->email;
        $this->marketingOptIn = (bool) Arr::get($this->customer->metadata ?? [], 'marketing_opt_in', false);
    }
}
