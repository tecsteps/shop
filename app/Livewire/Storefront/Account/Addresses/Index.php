<?php

namespace App\Livewire\Storefront\Account\Addresses;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Support\Storefront\Countries;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::storefront')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = '';

    /** @var array<string, string> */
    public array $form = self::BLANK_FORM;

    /** @var array<string, string> */
    private const array BLANK_FORM = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province' => '',
        'postal_code' => '',
        'country_code' => '',
        'phone' => '',
    ];

    /**
     * Open the modal with a blank form to add a new address.
     */
    public function create(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->label = '';
        $this->form = self::BLANK_FORM;
        $this->showForm = true;
    }

    /**
     * Open the modal pre-filled with an existing address.
     */
    public function edit(int $addressId): void
    {
        $address = $this->findAddress($addressId);

        $this->resetValidation();
        $this->editingId = $address->getKey();
        $this->label = (string) $address->label;
        $this->form = array_merge(self::BLANK_FORM, $address->toCheckoutAddress());
        $this->showForm = true;
    }

    /**
     * Create or update the address being edited. The first address a
     * customer saves automatically becomes the default.
     */
    public function save(): void
    {
        $this->validate(
            [
                'label' => ['nullable', 'string', 'max:255'],
                'form.first_name' => ['required', 'string', 'max:255'],
                'form.last_name' => ['required', 'string', 'max:255'],
                'form.address1' => ['required', 'string', 'max:255'],
                'form.address2' => ['nullable', 'string', 'max:255'],
                'form.city' => ['required', 'string', 'max:255'],
                'form.province' => ['nullable', 'string', 'max:255'],
                'form.postal_code' => ['required', 'string', 'max:32'],
                'form.country_code' => ['required', 'string', 'size:2'],
                'form.phone' => ['nullable', 'string', 'max:64'],
            ],
            [],
            [
                'form.first_name' => __('first name'),
                'form.last_name' => __('last name'),
                'form.address1' => __('address line 1'),
                'form.address2' => __('address line 2'),
                'form.city' => __('city'),
                'form.province' => __('state / province'),
                'form.postal_code' => __('postal code'),
                'form.country_code' => __('country'),
                'form.phone' => __('phone'),
            ],
        );

        $attributes = [
            'label' => trim($this->label),
            'address_json' => $this->addressJson(),
        ];

        if ($this->editingId !== null) {
            $this->findAddress($this->editingId)->update($attributes);
        } else {
            $this->addresses()->create($attributes + [
                'is_default' => ! $this->addresses()->where('is_default', true)->exists(),
            ]);
        }

        $this->showForm = false;
        $this->editingId = null;
    }

    /**
     * Delete an address. When the default address is removed, the most
     * recently added remaining address becomes the new default.
     */
    public function delete(int $addressId): void
    {
        $address = $this->findAddress($addressId);
        $wasDefault = $address->is_default;

        $address->delete();

        if ($wasDefault) {
            $this->addresses()->latest('id')->first()?->update(['is_default' => true]);
        }
    }

    /**
     * Mark an address as the default, flipping all others off.
     */
    public function setDefault(int $addressId): void
    {
        $address = $this->findAddress($addressId);

        DB::transaction(function () use ($address): void {
            $this->addresses()->whereKeyNot($address->getKey())->update(['is_default' => false]);

            $address->update(['is_default' => true]);
        });
    }

    public function render(): View
    {
        $addresses = $this->addresses()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return view('livewire.storefront.account.addresses.index', [
            'addresses' => $addresses,
        ])->title(__('Your Addresses'));
    }

    /**
     * The authenticated customer's addresses; addresses of other customers
     * can never be resolved (404).
     */
    protected function addresses(): HasMany
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return $customer->addresses();
    }

    protected function findAddress(int $addressId): CustomerAddress
    {
        /** @var CustomerAddress|null $address */
        $address = $this->addresses()->find($addressId);

        abort_if($address === null, 404);

        return $address;
    }

    /**
     * Map the form fields to the spec 01 address JSON shape ("zip" key),
     * preserving keys the form does not manage (company, province_code).
     *
     * @return array<string, string>
     */
    protected function addressJson(): array
    {
        $existing = $this->editingId !== null
            ? ($this->findAddress($this->editingId)->address_json ?? [])
            : [];

        return [
            'first_name' => trim($this->form['first_name']),
            'last_name' => trim($this->form['last_name']),
            'company' => (string) ($existing['company'] ?? ''),
            'address1' => trim($this->form['address1']),
            'address2' => trim($this->form['address2']),
            'city' => trim($this->form['city']),
            'province' => trim($this->form['province']),
            'province_code' => (string) ($existing['province_code'] ?? ''),
            'country' => Countries::name($this->form['country_code']),
            'country_code' => strtoupper(trim($this->form['country_code'])),
            'zip' => trim($this->form['postal_code']),
            'phone' => trim($this->form['phone']),
        ];
    }
}
