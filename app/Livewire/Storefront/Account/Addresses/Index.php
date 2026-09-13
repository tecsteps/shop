<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Livewire\Storefront\Concerns\ResolvesStore;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    use ResolvesStore;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $label = '';

    /** @var array<string, mixed> */
    public array $address = [];

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        $this->address = $this->blankAddress();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $record = $this->customerAddresses()->whereKey($id)->first();

        abort_if($record === null, 404);

        $this->editingId = $record->getKey();
        $this->label = (string) ($record->label ?? '');
        $this->address = $this->blankAddress((array) ($record->address_json ?? []));
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate(array_merge(
            ['label' => 'nullable|string|max:255'],
            $this->addressRules(),
        ));

        $payload = [
            'label' => $this->label !== '' ? $this->label : null,
            'address_json' => $this->addressPayload(),
        ];

        if ($this->editingId !== null) {
            $record = $this->customerAddresses()->whereKey($this->editingId)->first();

            abort_if($record === null, 404);

            $record->update($payload);
        } else {
            $payload['is_default'] = $this->customerAddresses()->count() === 0;
            $this->customer()->customerAddresses()->create($payload);
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function setDefault(int $id): void
    {
        $record = $this->customerAddresses()->whereKey($id)->first();

        abort_if($record === null, 404);

        $this->customerAddresses()->update(['is_default' => false]);
        $record->update(['is_default' => true]);
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        if ($this->confirmingDelete === null) {
            return;
        }

        $record = $this->customerAddresses()->whereKey($this->confirmingDelete)->first();

        abort_if($record === null, 404);

        $wasDefault = (bool) $record->is_default;
        $record->delete();
        $this->confirmingDelete = null;

        if ($wasDefault) {
            $first = $this->customerAddresses()->orderBy('id')->first();

            if ($first !== null) {
                $first->update(['is_default' => true]);
            }
        }
    }

    public function render()
    {
        $store = $this->store();
        $customer = Auth::guard('customer')->user();

        abort_if($customer === null, 401);

        $addresses = $customer->customerAddresses()->orderByDesc('is_default')->orderBy('id')->get();

        return view('livewire.storefront.account.addresses.index', [
            'store' => $store,
            'addresses' => $addresses,
        ])->layout('storefront.layouts.app', [
            'title' => 'Your Addresses - '.$store->name,
            'metaDescription' => 'Manage your addresses at '.$store->name,
        ]);
    }

    protected function customer(): \App\Models\Customer
    {
        $customer = Auth::guard('customer')->user();

        abort_if($customer === null, 401);

        return $customer;
    }

    protected function customerAddresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->customer()->customerAddresses();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->label = '';
        $this->address = $this->blankAddress();
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    protected function blankAddress(array $values = []): array
    {
        return array_merge([
            'first_name' => '',
            'last_name' => '',
            'company' => null,
            'address1' => '',
            'address2' => null,
            'city' => '',
            'province' => null,
            'province_code' => null,
            'country' => '',
            'country_code' => '',
            'postal_code' => '',
            'phone' => null,
        ], $values);
    }

    /**
     * @return array<string, string>
     */
    protected function addressRules(): array
    {
        return [
            'address.first_name' => 'required|string|max:255',
            'address.last_name' => 'required|string|max:255',
            'address.address1' => 'required|string|max:500',
            'address.address2' => 'nullable|string|max:500',
            'address.city' => 'required|string|max:255',
            'address.province' => 'nullable|string|max:255',
            'address.country_code' => 'required|string|size:2',
            'address.postal_code' => 'required|string|max:20',
            'address.phone' => 'nullable|string|max:50',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function addressPayload(): array
    {
        $names = [
            'DE' => 'Germany', 'AT' => 'Austria', 'FR' => 'France', 'NL' => 'Netherlands',
            'BE' => 'Belgium', 'IT' => 'Italy', 'ES' => 'Spain', 'US' => 'United States',
            'GB' => 'United Kingdom', 'CH' => 'Switzerland',
        ];

        $code = strtoupper((string) ($this->address['country_code'] ?? ''));

        return [
            'first_name' => $this->address['first_name'],
            'last_name' => $this->address['last_name'],
            'company' => $this->address['company'] ?? null,
            'address1' => $this->address['address1'],
            'address2' => $this->address['address2'] ?? null,
            'city' => $this->address['city'],
            'province' => $this->address['province'] ?? null,
            'province_code' => $this->address['province_code'] ?? null,
            'country' => $names[$code] ?? $code,
            'country_code' => $code,
            'postal_code' => $this->address['postal_code'],
            'phone' => $this->address['phone'] ?? null,
        ];
    }
}
