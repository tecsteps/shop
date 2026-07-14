<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\AdminComponent;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class Show extends AdminComponent
{
    use WithPagination;

    public Customer $customer;

    public ?CustomerAddress $editingAddress = null;

    public string $addressLabel = '';

    /** @var array<string, string> */
    public array $addressJson = [
        'first_name' => '', 'last_name' => '', 'company' => '', 'address1' => '', 'address2' => '',
        'city' => '', 'province' => '', 'province_code' => '', 'country' => '', 'country_code' => '', 'zip' => '', 'phone' => '',
    ];

    public bool $addressIsDefault = false;

    public function mount(Customer $customer): void
    {
        abort_unless((int) $customer->store_id === (int) $this->currentStore()->id, 404);
        $this->authorizeAction('view', $customer);
        $this->customer = $customer->load('addresses');
    }

    public function openAddressForm(CustomerAddress|int|null $address = null): void
    {
        $this->authorizeAction('update', $this->customer);
        $resolved = $address instanceof CustomerAddress ? $address : ($address ? $this->customer->addresses()->findOrFail($address) : null);
        if ($resolved) {
            abort_unless((int) $resolved->customer_id === (int) $this->customer->id, 404);
        }
        $this->editingAddress = $resolved;
        $this->addressLabel = (string) $resolved?->label;
        $this->addressJson = array_replace($this->emptyAddress(), (array) $resolved?->address_json);
        $this->addressIsDefault = (bool) ($resolved?->is_default ?? $this->customer->addresses->isEmpty());
    }

    public function saveAddress(): void
    {
        $this->authorizeAction('update', $this->customer);
        $validated = $this->validate([
            'addressLabel' => ['nullable', 'string', 'max:100'],
            'addressJson.first_name' => ['required', 'string', 'max:100'], 'addressJson.last_name' => ['required', 'string', 'max:100'],
            'addressJson.company' => ['nullable', 'string', 'max:255'], 'addressJson.address1' => ['required', 'string', 'max:255'],
            'addressJson.address2' => ['nullable', 'string', 'max:255'], 'addressJson.city' => ['required', 'string', 'max:100'],
            'addressJson.province' => ['nullable', 'string', 'max:100'], 'addressJson.province_code' => ['nullable', 'string', 'max:20'],
            'addressJson.country' => ['required', 'string', 'max:100'], 'addressJson.country_code' => ['required', 'string', 'size:2'],
            'addressJson.zip' => ['required', 'string', 'max:30'], 'addressJson.phone' => ['nullable', 'string', 'max:50'],
            'addressIsDefault' => ['boolean'],
        ]);
        DB::transaction(function () use ($validated): void {
            if ($validated['addressIsDefault']) {
                $this->customer->addresses()->update(['is_default' => false]);
            }
            $attributes = ['label' => $validated['addressLabel'] ?: null, 'address_json' => $validated['addressJson'], 'is_default' => $validated['addressIsDefault']];
            $attributes['address_json']['country_code'] = mb_strtoupper($attributes['address_json']['country_code']);
            $attributes['address_json']['province_code'] = mb_strtoupper($attributes['address_json']['province_code']);
            if ($this->editingAddress) {
                $this->editingAddress->update($attributes);
            } else {
                $this->customer->addresses()->create($attributes);
            }
        });
        $this->reloadCustomer();
        $this->modal('address-form')->close();
        $this->toast('Address saved.');
    }

    public function deleteAddress(int $addressId): void
    {
        $this->authorizeAction('update', $this->customer);
        $address = $this->customer->addresses()->findOrFail($addressId);
        $wasDefault = $address->is_default;
        $address->delete();
        if ($wasDefault) {
            $this->customer->addresses()->orderBy('id')->first()?->update(['is_default' => true]);
        }
        $this->reloadCustomer();
        $this->toast('Address deleted.');
    }

    public function setDefaultAddress(int $addressId): void
    {
        $this->authorizeAction('update', $this->customer);
        DB::transaction(function () use ($addressId): void {
            $address = $this->customer->addresses()->findOrFail($addressId);
            $this->customer->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
        $this->reloadCustomer();
        $this->toast('Default address updated.');
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return $this->customer->orders()->latest('placed_at')->paginate(10);
    }

    private function reloadCustomer(): void
    {
        $this->customer = $this->customer->refresh()->load('addresses');
    }

    /** @return array<string, string> */
    private function emptyAddress(): array
    {
        return ['first_name' => '', 'last_name' => '', 'company' => '', 'address1' => '', 'address2' => '', 'city' => '', 'province' => '', 'province_code' => '', 'country' => '', 'country_code' => '', 'zip' => '', 'phone' => ''];
    }

    public function render(): View
    {
        return $this->admin(view('admin.customers.show'), $this->customer->name ?: $this->customer->email, [['label' => 'Customers', 'url' => url('/admin/customers')], ['label' => $this->customer->name ?: $this->customer->email]]);
    }
}
