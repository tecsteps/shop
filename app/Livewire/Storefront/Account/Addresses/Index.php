<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public int $storeId;

    #[Locked]
    public int $customerId;

    public bool $showForm = false;

    public ?int $editingAddressId = null;

    public ?string $statusMessage = null;

    public string $addressLabel = '';

    /**
     * @var array{first_name: string, last_name: string, address1: string, address2: string, city: string, province_code: string, country: string, postal_code: string}
     */
    public array $address = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province_code' => '',
        'country' => 'DE',
        'postal_code' => '',
    ];

    public function mount(): void
    {
        $store = app('current_store');
        $customer = Auth::guard('customer')->user();

        abort_unless($store instanceof Store && $customer instanceof Customer, 404);

        $this->storeId = $store->getKey();
        $this->customerId = $customer->getKey();
    }

    public function openAddressForm(?int $addressId = null): void
    {
        $this->resetValidation();
        $this->statusMessage = null;
        $this->editingAddressId = $addressId;

        if ($addressId !== null) {
            $address = $this->addressRecord($addressId);

            $this->addressLabel = (string) $address->label;
            $this->address = array_merge($this->emptyAddress(), $address->address_json ?? []);
        } else {
            $this->resetAddressForm();
        }

        $this->showForm = true;
    }

    public function saveAddress(): void
    {
        $this->validate([
            'addressLabel' => ['nullable', 'string', 'max:255'],
            'address.first_name' => ['required', 'string', 'max:255'],
            'address.last_name' => ['required', 'string', 'max:255'],
            'address.address1' => ['required', 'string', 'max:255'],
            'address.address2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.province_code' => ['nullable', 'string', 'max:255'],
            'address.country' => ['required', 'string', 'size:2'],
            'address.postal_code' => ['required', 'string', 'max:32'],
        ]);

        $address = $this->editingAddressId !== null
            ? $this->addressRecord($this->editingAddressId)
            : new CustomerAddress(['customer_id' => $this->customerId]);

        $address->fill([
            'label' => $this->addressLabel !== '' ? $this->addressLabel : null,
            'address_json' => $this->address,
            'is_default' => $address->exists ? $address->is_default : ! $this->customer()->addresses()->exists(),
        ]);
        $address->save();

        $this->resetAddressForm();
        $this->showForm = false;
        $this->statusMessage = __('Address saved');
    }

    public function deleteAddress(int $addressId): void
    {
        $address = $this->addressRecord($addressId);
        $wasDefault = $address->is_default;

        $address->delete();

        if ($wasDefault) {
            $this->setFirstAddressAsDefault();
        }

        $this->statusMessage = __('Address deleted');
    }

    public function setDefaultAddress(int $addressId): void
    {
        $address = $this->addressRecord($addressId);

        DB::transaction(function () use ($address): void {
            CustomerAddress::query()
                ->where('customer_id', $this->customerId)
                ->update(['is_default' => false]);

            $address->forceFill(['is_default' => true])->save();
        });

        $this->statusMessage = __('Default address updated');
    }

    public function cancelAddressForm(): void
    {
        $this->resetValidation();
        $this->resetAddressForm();
        $this->showForm = false;
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $this->addresses(),
            'customer' => $this->customer(),
        ])->layout('layouts.storefront', [
            'title' => 'Addresses',
        ]);
    }

    /**
     * @return Collection<int, CustomerAddress>
     */
    private function addresses(): Collection
    {
        return CustomerAddress::query()
            ->where('customer_id', $this->customerId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();
    }

    private function customer(): Customer
    {
        return Customer::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->whereKey($this->customerId)
            ->firstOrFail();
    }

    private function addressRecord(int $addressId): CustomerAddress
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
        $this->address = $this->emptyAddress();
    }

    /**
     * @return array{first_name: string, last_name: string, address1: string, address2: string, city: string, province_code: string, country: string, postal_code: string}
     */
    private function emptyAddress(): array
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
