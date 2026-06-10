<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Customer;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
class Show extends Component
{
    use AuthorizesRequests, SendsToasts, WithPagination;

    #[Locked]
    public int $customerId;

    public ?int $editingAddressId = null;

    public string $addressLabel = '';

    /** @var array{first_name: string, last_name: string, address1: string, address2: string, city: string, province: string, zip: string, country_code: string} */
    public array $addressFields = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province' => '',
        'zip' => '',
        'country_code' => 'DE',
    ];

    public function mount(int $customer): void
    {
        $this->customerId = $customer;

        $this->authorize('view', $this->customer);
    }

    #[Computed]
    public function customer(): Customer
    {
        return Customer::query()
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->with('addresses')
            ->findOrFail($this->customerId);
    }

    /**
     * @return LengthAwarePaginator<int, \App\Models\Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return $this->customer->orders()
            ->orderByDesc('placed_at')
            ->paginate(10);
    }

    public function openAddressForm(?int $addressId = null): void
    {
        $this->resetErrorBag();
        $this->editingAddressId = $addressId;

        if ($addressId !== null) {
            $address = $this->customer->addresses()->findOrFail($addressId);
            $json = $address->address_json ?? [];

            $this->addressLabel = (string) ($address->label ?? '');
            $this->addressFields = [
                'first_name' => (string) ($json['first_name'] ?? ''),
                'last_name' => (string) ($json['last_name'] ?? ''),
                'address1' => (string) ($json['address1'] ?? ''),
                'address2' => (string) ($json['address2'] ?? ''),
                'city' => (string) ($json['city'] ?? ''),
                'province' => (string) ($json['province'] ?? ''),
                'zip' => (string) ($json['zip'] ?? ''),
                'country_code' => (string) ($json['country_code'] ?? 'DE'),
            ];
        } else {
            $this->addressLabel = '';
            $this->addressFields = [
                'first_name' => '',
                'last_name' => '',
                'address1' => '',
                'address2' => '',
                'city' => '',
                'province' => '',
                'zip' => '',
                'country_code' => 'DE',
            ];
        }

        Flux::modal('address-form')->show();
    }

    public function saveAddress(): void
    {
        $this->authorize('update', $this->customer);

        $this->validate([
            'addressLabel' => ['nullable', 'string', 'max:255'],
            'addressFields.address1' => ['required', 'string', 'max:255'],
            'addressFields.city' => ['required', 'string', 'max:255'],
            'addressFields.zip' => ['required', 'string', 'max:32'],
            'addressFields.country_code' => ['required', 'string', 'size:2'],
        ]);

        $payload = [
            'label' => $this->addressLabel !== '' ? $this->addressLabel : null,
            'address_json' => array_filter($this->addressFields, fn (string $value): bool => trim($value) !== ''),
        ];

        if ($this->editingAddressId !== null) {
            $this->customer->addresses()->findOrFail($this->editingAddressId)->update($payload);
        } else {
            $this->customer->addresses()->create($payload + [
                'is_default' => $this->customer->addresses()->count() === 0,
            ]);
        }

        Flux::modal('address-form')->close();

        unset($this->customer);
        $this->toast(__('Customer saved'));
    }

    public function deleteAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer);

        $address = $this->customer->addresses()->findOrFail($addressId);
        $wasDefault = $address->is_default;

        $address->delete();

        if ($wasDefault) {
            $this->customer->addresses()->orderByDesc('id')->first()?->update(['is_default' => true]);
        }

        unset($this->customer);
        $this->toast(__('Address removed.'));
    }

    public function setDefaultAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer);

        $address = $this->customer->addresses()->findOrFail($addressId);

        $this->customer->addresses()->whereKeyNot($address->getKey())->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        unset($this->customer);
        $this->toast(__('Default address updated.'));
    }

    public function render(): View
    {
        return view('livewire.admin.customers.show')
            ->title($this->customer->name ?: $this->customer->email);
    }
}
