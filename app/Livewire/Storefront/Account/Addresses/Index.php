<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    use EnsuresStore;

    public string $label = 'Home';

    public string $firstName = '';

    public string $lastName = '';

    public string $line1 = '';

    public string $line2 = '';

    public string $city = '';

    public string $postalCode = '';

    public string $country = 'DE';

    public bool $isDefault = false;

    public function mount(): void
    {
        $this->ensureCurrentStore();
    }

    public function addAddress(): void
    {
        $this->validate([
            'label' => 'required|string|max:60',
            'firstName' => 'required|string|max:120',
            'lastName' => 'required|string|max:120',
            'line1' => 'required|string|max:255',
            'city' => 'required|string|max:120',
            'postalCode' => 'required|string|max:30',
            'country' => 'required|string|size:2',
        ]);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if ($this->isDefault) {
            CustomerAddress::query()
                ->where('customer_id', $customer->id)
                ->update(['is_default' => false]);
        }

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => $this->label,
            'is_default' => $this->isDefault,
            'address_json' => [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'address1' => $this->line1,
                'address2' => $this->line2,
                'city' => $this->city,
                'postal_code' => $this->postalCode,
                'country' => $this->country,
            ],
        ]);

        $this->reset(['label', 'firstName', 'lastName', 'line1', 'line2', 'city', 'postalCode', 'isDefault']);
        $this->label = 'Home';
        $this->country = 'DE';
    }

    public function makeDefault(int $addressId): void
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->update(['is_default' => false]);

        CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->where('id', $addressId)
            ->update(['is_default' => true]);
    }

    public function deleteAddress(int $addressId): void
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->where('id', $addressId)
            ->delete();
    }

    public function render(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $addresses = CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->get();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $addresses,
        ]);
    }
}
