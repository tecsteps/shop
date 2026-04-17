<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Addresses extends Component
{
    public ?int $editingId = null;

    public string $label = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $address1 = '';

    public string $city = '';

    public string $province_code = '';

    public string $country_code = 'US';

    public string $postal_code = '';

    public bool $is_default = false;

    public function save(): void
    {
        $this->validate([
            'label' => 'nullable|string|max:100',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'address1' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'country_code' => 'required|string|size:2',
            'postal_code' => 'required|string|max:20',
        ]);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $payload = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'address1' => $this->address1,
            'city' => $this->city,
            'province_code' => $this->province_code,
            'country_code' => $this->country_code,
            'postal_code' => $this->postal_code,
        ];

        if ($this->editingId !== null) {
            $address = CustomerAddress::query()
                ->where('customer_id', $customer->getKey())
                ->where('id', $this->editingId)
                ->firstOrFail();

            $address->label = $this->label ?: null;
            $address->address_json = $payload;
            $address->is_default = $this->is_default ? 1 : 0;
            $address->save();
        } else {
            $address = CustomerAddress::query()->create([
                'customer_id' => $customer->getKey(),
                'label' => $this->label ?: null,
                'address_json' => $payload,
                'is_default' => $this->is_default ? 1 : 0,
            ]);
        }

        if ($this->is_default) {
            CustomerAddress::query()
                ->where('customer_id', $customer->getKey())
                ->where('id', '!=', $address->getKey())
                ->update(['is_default' => 0]);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $address = CustomerAddress::query()
            ->where('customer_id', $customer->getKey())
            ->where('id', $id)
            ->firstOrFail();

        $data = $address->address_json ?? [];
        $this->editingId = $id;
        $this->label = (string) ($address->label ?? '');
        $this->first_name = (string) ($data['first_name'] ?? '');
        $this->last_name = (string) ($data['last_name'] ?? '');
        $this->address1 = (string) ($data['address1'] ?? '');
        $this->city = (string) ($data['city'] ?? '');
        $this->province_code = (string) ($data['province_code'] ?? '');
        $this->country_code = (string) ($data['country_code'] ?? 'US');
        $this->postal_code = (string) ($data['postal_code'] ?? '');
        $this->is_default = (bool) $address->is_default;
    }

    public function delete(int $id): void
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        CustomerAddress::query()
            ->where('customer_id', $customer->getKey())
            ->where('id', $id)
            ->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->label = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->address1 = '';
        $this->city = '';
        $this->province_code = '';
        $this->country_code = 'US';
        $this->postal_code = '';
        $this->is_default = false;
    }

    public function render(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return view('livewire.storefront.account.addresses', [
            'addresses' => CustomerAddress::query()
                ->where('customer_id', $customer->getKey())
                ->orderByDesc('is_default')
                ->get(),
        ]);
    }
}
