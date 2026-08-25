<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Livewire\Admin\Concerns\FormatsMoney;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use DispatchesToasts, FormatsMoney, WithPagination;

    #[Layout('layouts.admin.app')]
    public Customer $customer;

    public bool $showAddressForm = false;

    public ?int $editingAddressId = null;

    public string $addressLabel = '';

    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public string $addressCity = '';

    public string $addressState = '';

    public string $addressZip = '';

    public string $addressCountry = 'US';

    public bool $addressDefault = false;

    public function mount(Customer $customer): void
    {
        $this->authorize('view', $customer);

        $this->customer = $customer->load('orders');
    }

    /**
     * @return list<array{id: int, label: ?string, is_default: bool, address: array<string, mixed>}>
     */
    #[Computed]
    public function addresses(): array
    {
        return DB::table('customer_addresses')
            ->where('customer_id', $this->customer->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'label' => $row->label,
                'is_default' => (bool) $row->is_default,
                'address' => json_decode((string) $row->address_json, true) ?: [],
            ])
            ->all();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return $this->customer->orders()->latest('placed_at')->paginate(10);
    }

    public function openAddressForm(?int $addressId = null): void
    {
        $this->resetAddressForm();
        $this->editingAddressId = $addressId;

        if ($addressId) {
            $row = DB::table('customer_addresses')
                ->where('id', $addressId)
                ->where('customer_id', $this->customer->id)
                ->first();

            if ($row) {
                $address = json_decode((string) $row->address_json, true) ?: [];

                $this->addressLabel = (string) ($row->label ?? '');
                $this->addressLine1 = (string) ($address['line1'] ?? '');
                $this->addressLine2 = (string) ($address['line2'] ?? '');
                $this->addressCity = (string) ($address['city'] ?? '');
                $this->addressState = (string) ($address['state'] ?? '');
                $this->addressZip = (string) ($address['zip'] ?? '');
                $this->addressCountry = (string) ($address['country'] ?? 'US');
                $this->addressDefault = (bool) $row->is_default;
            }
        }

        $this->showAddressForm = true;
    }

    public function saveAddress(): void
    {
        $this->authorize('update', $this->customer);

        $this->validate([
            'addressLabel' => ['nullable', 'string', 'max:255'],
            'addressLine1' => ['required', 'string', 'max:255'],
            'addressLine2' => ['nullable', 'string', 'max:255'],
            'addressCity' => ['required', 'string', 'max:255'],
            'addressState' => ['nullable', 'string', 'max:255'],
            'addressZip' => ['required', 'string', 'max:32'],
            'addressCountry' => ['required', 'string', 'max:255'],
        ]);

        $payload = [
            'label' => $this->addressLabel !== '' ? $this->addressLabel : null,
            'address_json' => json_encode([
                'line1' => $this->addressLine1,
                'line2' => $this->addressLine2,
                'city' => $this->addressCity,
                'state' => $this->addressState,
                'zip' => $this->addressZip,
                'country' => $this->addressCountry,
            ]),
            'is_default' => $this->addressDefault,
        ];

        $newAddressId = null;

        if ($this->editingAddressId) {
            DB::table('customer_addresses')
                ->where('id', $this->editingAddressId)
                ->where('customer_id', $this->customer->id)
                ->update($payload);

            $newAddressId = $this->editingAddressId;
        } else {
            $newAddressId = DB::table('customer_addresses')->insertGetId(['customer_id' => $this->customer->id] + $payload);
        }

        if ($this->addressDefault) {
            DB::table('customer_addresses')
                ->where('customer_id', $this->customer->id)
                ->where('id', '!=', $newAddressId)
                ->update(['is_default' => false]);
        }

        $this->showAddressForm = false;
        $this->toast('Address saved');
    }

    public function deleteAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer);

        DB::table('customer_addresses')
            ->where('id', $addressId)
            ->where('customer_id', $this->customer->id)
            ->delete();

        $this->toast('Address deleted');
    }

    public function setDefaultAddress(int $addressId): void
    {
        $this->authorize('update', $this->customer);

        DB::table('customer_addresses')->where('customer_id', $this->customer->id)->update(['is_default' => false]);
        DB::table('customer_addresses')->where('id', $addressId)->where('customer_id', $this->customer->id)->update(['is_default' => true]);

        $this->toast('Default address updated');
    }

    private function resetAddressForm(): void
    {
        $this->addressLabel = '';
        $this->addressLine1 = '';
        $this->addressLine2 = '';
        $this->addressCity = '';
        $this->addressState = '';
        $this->addressZip = '';
        $this->addressCountry = 'US';
        $this->addressDefault = false;
    }

    public function render()
    {
        return view('livewire.admin.customers.show');
    }
}
