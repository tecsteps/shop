<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Storefront\StorefrontController;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends StorefrontController
{
    public function index(Request $request)
    {
        $customer = $this->customer();
        abort_if(! $customer, 403);

        $addresses = CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return view('customer.addresses.index', $this->storefrontViewData($request, [
            'addresses' => $addresses,
            'title' => 'Address Book',
            'metaDescription' => 'Manage your shipping and billing addresses.',
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $this->customer();
        abort_if(! $customer, 403);

        $validated = $this->validateAddress($request);

        $address = CustomerAddress::query()->create([
            'customer_id' => $customer->id,
            'label' => $validated['label'],
            'is_default' => (bool) $validated['is_default'],
            'address_json' => $validated['address'],
        ]);

        $this->syncDefaultAddress($customer->id, $address, (bool) $validated['is_default']);

        return redirect()->route('storefront.account.addresses.index')->with('status', 'Address added.');
    }

    public function update(Request $request, int $addressId): RedirectResponse
    {
        $customer = $this->customer();
        abort_if(! $customer, 403);

        $address = $this->findAddress($customer->id, $addressId);
        $validated = $this->validateAddress($request);

        $address->label = $validated['label'];
        $address->is_default = (bool) $validated['is_default'];
        $address->address_json = $validated['address'];
        $address->save();

        $this->syncDefaultAddress($customer->id, $address, (bool) $validated['is_default']);

        return redirect()->route('storefront.account.addresses.index')->with('status', 'Address updated.');
    }

    public function destroy(int $addressId): RedirectResponse
    {
        $customer = $this->customer();
        abort_if(! $customer, 403);

        $address = $this->findAddress($customer->id, $addressId);
        $wasDefault = $address->is_default;

        $address->delete();

        if ($wasDefault) {
            $nextAddress = CustomerAddress::query()
                ->where('customer_id', $customer->id)
                ->orderBy('id')
                ->first();

            if ($nextAddress) {
                $nextAddress->is_default = true;
                $nextAddress->save();
            }
        }

        return redirect()->route('storefront.account.addresses.index')->with('status', 'Address deleted.');
    }

    public function setDefault(int $addressId): RedirectResponse
    {
        $customer = $this->customer();
        abort_if(! $customer, 403);

        $address = $this->findAddress($customer->id, $addressId);
        $this->syncDefaultAddress($customer->id, $address, true);

        return redirect()->route('storefront.account.addresses.index')->with('status', 'Default address updated.');
    }

    protected function findAddress(int $customerId, int $addressId): CustomerAddress
    {
        return CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->whereKey($addressId)
            ->firstOrFail();
    }

    /**
     * @return array{label:?string,is_default:bool,address:array<string,string>}
     */
    protected function validateAddress(Request $request): array
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'is_default' => ['nullable', 'boolean'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'province_code' => ['nullable', 'string', 'max:10'],
            'country' => ['required', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        return [
            'label' => $validated['label'] ?: null,
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'address' => [
                'first_name' => trim((string) $validated['first_name']),
                'last_name' => trim((string) $validated['last_name']),
                'company' => trim((string) ($validated['company'] ?? '')),
                'address1' => trim((string) $validated['address1']),
                'address2' => trim((string) ($validated['address2'] ?? '')),
                'city' => trim((string) $validated['city']),
                'province' => trim((string) ($validated['province'] ?? '')),
                'province_code' => strtoupper(trim((string) ($validated['province_code'] ?? ''))),
                'country' => trim((string) $validated['country']),
                'country_code' => strtoupper(trim((string) $validated['country_code'])),
                'zip' => trim((string) $validated['zip']),
                'phone' => trim((string) ($validated['phone'] ?? '')),
            ],
        ];
    }

    protected function syncDefaultAddress(int $customerId, CustomerAddress $selectedAddress, bool $shouldSetDefault): void
    {
        if (! $shouldSetDefault) {
            $hasDefault = CustomerAddress::query()
                ->where('customer_id', $customerId)
                ->where('is_default', true)
                ->whereKeyNot($selectedAddress->id)
                ->exists();

            if (! $hasDefault) {
                $selectedAddress->is_default = true;
                $selectedAddress->save();
            }

            return;
        }

        CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->whereKeyNot($selectedAddress->id)
            ->update(['is_default' => false]);

        if (! $selectedAddress->is_default) {
            $selectedAddress->is_default = true;
            $selectedAddress->save();
        }
    }
}
