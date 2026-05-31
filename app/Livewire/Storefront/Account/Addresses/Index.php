<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Customer address book: list, create, edit, delete, and set-default.
 *
 * Addresses are stored as a JSON blob ({@see CustomerAddress::$address_json})
 * plus a label and is_default flag, scoped to the authenticated customer. All
 * actions re-scope to the current customer so one customer can never touch
 * another's addresses.
 */
#[Layout('storefront.layouts.app')]
#[Title('Addresses')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = '';

    /** @var array<string, string> */
    public array $form = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province' => '',
        'postal_code' => '',
        'country' => '',
        'phone' => '',
    ];

    public bool $isDefault = false;

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'form.first_name' => 'required|string|max:255',
            'form.last_name' => 'required|string|max:255',
            'form.address1' => 'required|string|max:255',
            'form.city' => 'required|string|max:255',
            'form.postal_code' => 'required|string|max:32',
            'form.country' => 'required|string|size:2',
        ];
    }

    public function addAddress(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $addressId): void
    {
        $address = $this->customer()->addresses()->findOrFail($addressId);

        $this->editingId = $address->id;
        $this->label = (string) $address->label;
        $this->form = array_merge($this->form, $address->address_json ?? []);
        $this->isDefault = $address->is_default;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $customer = $this->customer();

        $address = $this->editingId !== null
            ? $customer->addresses()->findOrFail($this->editingId)
            : new CustomerAddress(['customer_id' => $customer->id]);

        $address->customer_id = $customer->id;
        $address->label = $this->label !== '' ? $this->label : null;
        $address->address_json = $this->form;
        $address->is_default = $this->isDefault;
        $address->save();

        if ($this->isDefault) {
            $this->clearOtherDefaults($customer, $address->id);
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $addressId): void
    {
        $this->customer()->addresses()->where('id', $addressId)->delete();
    }

    public function setDefault(int $addressId): void
    {
        $customer = $this->customer();
        $customer->addresses()->where('id', $addressId)->update(['is_default' => true]);
        $this->clearOtherDefaults($customer, $addressId);
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render()
    {
        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $this->customer()->addresses()->orderByDesc('is_default')->get(),
        ]);
    }

    private function clearOtherDefaults(Customer $customer, int $keepId): void
    {
        $customer->addresses()
            ->where('id', '!=', $keepId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'label', 'isDefault']);
        $this->form = [
            'first_name' => '', 'last_name' => '', 'address1' => '', 'address2' => '',
            'city' => '', 'province' => '', 'postal_code' => '', 'country' => '', 'phone' => '',
        ];
        $this->resetValidation();
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }
}
