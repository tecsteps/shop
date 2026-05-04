<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Locked]
    public int $storeId;

    #[Locked]
    public int $customerId;

    public ?int $editingAddressId = null;

    public string $addressLabel = '';

    /**
     * @var array{first_name: string, last_name: string, address1: string, address2: string, city: string, province_code: string, country: string, postal_code: string}
     */
    public array $addressJson = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province_code' => '',
        'country' => 'DE',
        'postal_code' => '',
    ];

    public function mount(Customer $customer): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $customer = Customer::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereKey($customer->getKey())
            ->first();

        abort_unless($customer instanceof Customer, 404);

        $this->authorize('view', $customer);

        $this->storeId = $store->getKey();
        $this->customerId = $customer->getKey();
    }

    public function openAddressForm(?int $addressId = null): void
    {
        $this->editingAddressId = $addressId;

        if ($addressId) {
            $address = $this->address($addressId);
            $this->addressLabel = (string) $address->label;
            $this->addressJson = array_merge($this->emptyAddressJson(), $address->address_json ?? []);
        } else {
            $this->resetAddressForm();
        }

        $this->modal('address-form')->show();
    }

    public function saveAddress(): void
    {
        $this->authorize('update', $this->customer());

        $this->validate([
            'addressLabel' => ['nullable', 'string', 'max:255'],
            'addressJson.first_name' => ['nullable', 'string', 'max:255'],
            'addressJson.last_name' => ['nullable', 'string', 'max:255'],
            'addressJson.address1' => ['required', 'string', 'max:255'],
            'addressJson.address2' => ['nullable', 'string', 'max:255'],
            'addressJson.city' => ['required', 'string', 'max:255'],
            'addressJson.province_code' => ['nullable', 'string', 'max:255'],
            'addressJson.country' => ['required', 'string', 'size:2'],
            'addressJson.postal_code' => ['required', 'string', 'max:32'],
        ]);

        $address = $this->editingAddressId
            ? $this->address($this->editingAddressId)
            : new CustomerAddress(['customer_id' => $this->customerId]);

        $address->fill([
            'label' => $this->addressLabel !== '' ? $this->addressLabel : null,
            'address_json' => $this->addressJson,
            'is_default' => $address->exists ? $address->is_default : ! $this->customer()->addresses()->exists(),
        ]);
        $address->save();

        $this->resetAddressForm();
        $this->modal('address-form')->close();
        $this->dispatch('toast', type: 'success', message: __('Address saved'));
    }

    public function deleteAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer());

        $address = $this->address($addressId);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $this->setFirstAddressAsDefault();
        }

        $this->dispatch('toast', type: 'success', message: __('Address deleted'));
    }

    public function setDefaultAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer());

        $address = $this->address($addressId);

        DB::transaction(function () use ($address): void {
            CustomerAddress::query()
                ->where('customer_id', $this->customerId)
                ->update(['is_default' => false]);

            $address->forceFill(['is_default' => true])->save();
        });

        $this->dispatch('toast', type: 'success', message: __('Default address updated'));
    }

    public function orders(): LengthAwarePaginator
    {
        return Order::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->where('customer_id', $this->customerId)
            ->latest('placed_at')
            ->latest('id')
            ->paginate(10);
    }

    public function render(): mixed
    {
        $customer = $this->customer();

        return view('livewire.admin.customers.show', [
            'customer' => $customer,
            'orders' => $this->orders(),
        ])->layout('layouts.app', [
            'title' => $customer->name ?: $customer->email,
        ]);
    }

    private function customer(): Customer
    {
        return Customer::withoutGlobalScopes()
            ->with(['addresses' => fn ($query) => $query->orderByDesc('is_default')->orderBy('id')])
            ->where('store_id', $this->storeId)
            ->whereKey($this->customerId)
            ->firstOrFail();
    }

    private function address(int $addressId): CustomerAddress
    {
        return CustomerAddress::query()
            ->where('customer_id', $this->customerId)
            ->whereKey($addressId)
            ->firstOrFail();
    }

    private function setFirstAddressAsDefault(): void
    {
        $nextAddress = CustomerAddress::query()
            ->where('customer_id', $this->customerId)
            ->oldest('id')
            ->first();

        if ($nextAddress instanceof CustomerAddress) {
            $nextAddress->forceFill(['is_default' => true])->save();
        }
    }

    private function resetAddressForm(): void
    {
        $this->editingAddressId = null;
        $this->addressLabel = '';
        $this->addressJson = $this->emptyAddressJson();
    }

    /**
     * @return array{first_name: string, last_name: string, address1: string, address2: string, city: string, province_code: string, country: string, postal_code: string}
     */
    private function emptyAddressJson(): array
    {
        return [
            'first_name' => '',
            'last_name' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'province_code' => '',
            'country' => 'DE',
            'postal_code' => '',
        ];
    }
}
