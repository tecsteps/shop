<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends AccountController
{
    public function index(Request $request): View
    {
        $customer = $this->currentCustomer($request);

        $addresses = $customer->addresses()
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();

        return view('storefront.account.addresses.index', [
            'customer' => $customer,
            'addresses' => $addresses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $this->currentCustomer($request);
        $payload = $this->validatedPayload($request);
        $isDefault = (bool) ($payload['is_default'] ?? false);

        if ($isDefault) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $customer->addresses()->create([
            'label' => $payload['label'] ?? null,
            'address_json' => $this->addressJson($payload),
            'is_default' => $isDefault || ! $customer->addresses()->exists(),
        ]);

        return redirect()->route('account.addresses.index')
            ->with('status', 'Address saved.');
    }

    public function update(Request $request, int $address): RedirectResponse
    {
        $customer = $this->currentCustomer($request);
        $record = $this->findAddress($customer, $address);
        $payload = $this->validatedPayload($request);
        $isDefault = (bool) ($payload['is_default'] ?? false);

        if ($isDefault) {
            $customer->addresses()
                ->where('id', '!=', $record->id)
                ->update(['is_default' => false]);
        }

        $record->fill([
            'label' => $payload['label'] ?? null,
            'address_json' => $this->addressJson($payload),
            'is_default' => $isDefault,
        ]);
        $record->save();

        if (! $customer->addresses()->where('is_default', true)->exists()) {
            $firstAddress = $customer->addresses()->orderBy('id')->first();

            if ($firstAddress instanceof CustomerAddress) {
                $firstAddress->is_default = true;
                $firstAddress->save();
            }
        }

        return redirect()->route('account.addresses.index')
            ->with('status', 'Address updated.');
    }

    public function destroy(Request $request, int $address): RedirectResponse
    {
        $customer = $this->currentCustomer($request);
        $record = $this->findAddress($customer, $address);
        $wasDefault = (bool) $record->is_default;

        $record->delete();

        if ($wasDefault) {
            $firstAddress = $customer->addresses()->orderBy('id')->first();

            if ($firstAddress instanceof CustomerAddress) {
                $firstAddress->is_default = true;
                $firstAddress->save();
            }
        }

        return redirect()->route('account.addresses.index')
            ->with('status', 'Address removed.');
    }

    /**
     * @return array<string, string|bool|null>
     */
    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'province_code' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['required', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $validated['is_default'] = filter_var($validated['is_default'] ?? false, FILTER_VALIDATE_BOOL);

        return $validated;
    }

    /**
     * @param  array<string, string|bool|null>  $payload
     * @return array<string, string>
     */
    private function addressJson(array $payload): array
    {
        $json = [];

        foreach ([
            'first_name',
            'last_name',
            'address1',
            'address2',
            'city',
            'province',
            'province_code',
            'postal_code',
            'country',
            'country_code',
            'phone',
        ] as $field) {
            $value = $payload[$field] ?? null;

            if (! is_string($value)) {
                continue;
            }

            $trimmed = trim($value);

            if ($trimmed === '') {
                continue;
            }

            $json[$field] = $field === 'country_code' || $field === 'province_code'
                ? strtoupper($trimmed)
                : $trimmed;
        }

        return $json;
    }

    private function findAddress(Customer $customer, int $addressId): CustomerAddress
    {
        /** @var CustomerAddress $address */
        $address = $customer->addresses()
            ->whereKey($addressId)
            ->firstOrFail();

        return $address;
    }
}
