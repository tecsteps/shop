<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $company = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $postal_code = '';

    public string $region = '';

    public string $country_code = '';

    public string $phone = '';

    public bool $is_default = false;

    protected function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:30'],
            'region' => ['nullable', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_default' => ['boolean'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $address = $this->customerAddresses()->findOrFail($id);
        $this->editingId = $address->id;
        $this->label = (string) ($address->label ?? '');
        $this->is_default = (bool) $address->is_default;

        $json = $address->address_json ?? [];
        foreach (['first_name', 'last_name', 'company', 'address1', 'address2', 'city', 'postal_code', 'region', 'country_code', 'phone'] as $key) {
            $this->{$key} = (string) ($json[$key] ?? '');
        }

        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        $data = $this->validate();
        $customer = auth('customer')->user();

        if (! $customer) {
            return;
        }

        $payload = [
            'customer_id' => $customer->id,
            'label' => $data['label'] ?: null,
            'address_json' => collect($data)
                ->only(['first_name', 'last_name', 'company', 'address1', 'address2', 'city', 'postal_code', 'region', 'country_code', 'phone'])
                ->filter(fn ($v): bool => $v !== '' && $v !== null)
                ->all(),
            'is_default' => $data['is_default'] ?? false,
        ];

        DB::transaction(function () use ($customer, $payload): void {
            if ($payload['is_default']) {
                CustomerAddress::query()
                    ->where('customer_id', $customer->id)
                    ->update(['is_default' => false]);
            }

            if ($this->editingId) {
                CustomerAddress::query()
                    ->where('customer_id', $customer->id)
                    ->where('id', $this->editingId)
                    ->update($payload);
            } else {
                $address = CustomerAddress::query()->create($payload);

                if ($customer->addresses()->count() === 1) {
                    $address->update(['is_default' => true]);
                }
            }
        });

        $this->resetForm();
        $this->showForm = false;
    }

    public function setDefault(int $id): void
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return;
        }

        DB::transaction(function () use ($customer, $id): void {
            CustomerAddress::query()
                ->where('customer_id', $customer->id)
                ->update(['is_default' => false]);

            CustomerAddress::query()
                ->where('customer_id', $customer->id)
                ->where('id', $id)
                ->update(['is_default' => true]);
        });
    }

    public function delete(int $id): void
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return;
        }

        $address = $this->customerAddresses()->find($id);

        if (! $address) {
            return;
        }

        $wasDefault = (bool) $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $this->customerAddresses()->orderBy('id')->first();
            $next?->update(['is_default' => true]);
        }
    }

    public function render()
    {
        $addresses = $this->customerAddresses()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $addresses,
        ]);
    }

    protected function customerAddresses()
    {
        $customer = auth('customer')->user();

        return CustomerAddress::query()->where('customer_id', $customer?->id ?? 0);
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->label = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->company = '';
        $this->address1 = '';
        $this->address2 = '';
        $this->city = '';
        $this->postal_code = '';
        $this->region = '';
        $this->country_code = '';
        $this->phone = '';
        $this->is_default = false;
        $this->resetErrorBag();
    }
}
