<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    use InteractsWithStore;

    public bool $showForm = false;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public bool $setAsDefault = false;

    public ?int $deleteTarget = null;

    public function openCreate(): void
    {
        $this->reset('form', 'setAsDefault', 'editingId');
        $this->form = $this->emptyForm();
        $this->setAsDefault = $this->addresses === [];
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $customer = $this->customer();

        $row = DB::table('customer_addresses')
            ->where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (! $row) {
            return;
        }

        $this->reset('form', 'setAsDefault');
        $this->form = array_replace($this->emptyForm(), json_decode((string) $row->address_json, true) ?? []);
        $this->form['label'] = $row->label;
        $this->editingId = $id;
        $this->setAsDefault = (bool) $row->is_default;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->reset('form', 'setAsDefault');
    }

    public function save(): void
    {
        $this->validate([
            'form.first_name' => ['required', 'string', 'max:255'],
            'form.last_name' => ['required', 'string', 'max:255'],
            'form.address1' => ['required', 'string', 'max:500'],
            'form.city' => ['required', 'string', 'max:255'],
            'form.country_code' => ['required', 'string', 'size:2'],
            'form.postal_code' => ['required', 'string', 'max:20'],
            'form.label' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = $this->customer();

        $address = array_intersect_key($this->form, array_flip([
            'first_name', 'last_name', 'company', 'address1', 'address2',
            'city', 'province', 'province_code', 'country', 'country_code',
            'postal_code', 'phone',
        ]));

        $countryCode = (string) ($this->form['country_code'] ?? '');
        $address['country'] = $this->countryName($countryCode);

        $isFirst = DB::table('customer_addresses')->where('customer_id', $customer->id)->doesntExist();

        DB::transaction(function () use ($customer, $address, $isFirst) {
            $isDefault = $this->setAsDefault || $isFirst;

            if ($this->editingId) {
                DB::table('customer_addresses')
                    ->where('customer_id', $customer->id)
                    ->where('id', $this->editingId)
                    ->update([
                        'label' => $this->form['label'] ?? null,
                        'address_json' => json_encode($address),
                        'is_default' => $isDefault,
                    ]);

                $id = $this->editingId;
            } else {
                $id = DB::table('customer_addresses')->insertGetId([
                    'customer_id' => $customer->id,
                    'label' => $this->form['label'] ?? null,
                    'address_json' => json_encode($address),
                    'is_default' => $isDefault,
                ]);
            }

            if ($isDefault) {
                DB::table('customer_addresses')
                    ->where('customer_id', $customer->id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }
        });

        $this->closeForm();
    }

    public function setDefault(int $id): void
    {
        $customer = $this->customer();

        DB::transaction(function () use ($customer, $id) {
            DB::table('customer_addresses')
                ->where('customer_id', $customer->id)
                ->update(['is_default' => false]);

            DB::table('customer_addresses')
                ->where('customer_id', $customer->id)
                ->where('id', $id)
                ->update(['is_default' => true]);
        });
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteTarget = $id;
    }

    public function cancelDelete(): void
    {
        $this->deleteTarget = null;
    }

    public function delete(): void
    {
        $customer = $this->customer();

        DB::table('customer_addresses')
            ->where('customer_id', $customer->id)
            ->where('id', $this->deleteTarget)
            ->delete();

        $this->deleteTarget = null;
    }

    /**
     * @return list<array{id: int, label: string|null, is_default: bool, address: array<string, mixed>}>
     */
    #[Computed]
    public function addresses(): array
    {
        $customer = $this->customer();

        return DB::table('customer_addresses')
            ->where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'label' => $row->label,
                'is_default' => (bool) $row->is_default,
                'address' => json_decode((string) $row->address_json, true) ?? [],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyForm(): array
    {
        return [
            'label' => '',
            'first_name' => '',
            'last_name' => '',
            'company' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'province' => '',
            'country_code' => '',
            'postal_code' => '',
            'phone' => '',
        ];
    }

    private function countryName(string $code): string
    {
        $countries = [
            'DE' => 'Germany', 'AT' => 'Austria', 'BE' => 'Belgium', 'CH' => 'Switzerland',
            'DK' => 'Denmark', 'ES' => 'Spain', 'FI' => 'Finland', 'FR' => 'France',
            'GB' => 'United Kingdom', 'IE' => 'Ireland', 'IT' => 'Italy', 'LU' => 'Luxembourg',
            'NL' => 'Netherlands', 'NO' => 'Norway', 'PL' => 'Poland', 'PT' => 'Portugal',
            'SE' => 'Sweden', 'US' => 'United States', 'CA' => 'Canada', 'AU' => 'Australia',
        ];

        return $countries[$code] ?? $code;
    }
}
