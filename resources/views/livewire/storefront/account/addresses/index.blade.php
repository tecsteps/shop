<x-storefront.account-shell :customer="$customer">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Account', 'url' => route('account.dashboard')],
        ['label' => 'Addresses'],
    ]" />

    <div class="mt-8 space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">Addresses</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Saved shipping details for faster checkout.</p>
            </div>

            <flux:button wire:click="openAddressForm" variant="primary" icon="plus">Add address</flux:button>
        </div>

        @if ($statusMessage)
            <flux:callout color="green">
                {{ $statusMessage }}
            </flux:callout>
        @endif

        @if ($showForm)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
                <form wire:submit="saveAddress" class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ $editingAddressId ? 'Edit address' : 'Add address' }}</flux:heading>
                    </div>

                    <flux:input wire:model="addressLabel" label="Label" placeholder="Home, Office..." />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="address.first_name" label="First name" autocomplete="given-name" />
                        <flux:input wire:model="address.last_name" label="Last name" autocomplete="family-name" />

                        <div class="sm:col-span-2">
                            <flux:input wire:model="address.address1" label="Address line 1" autocomplete="address-line1" />
                            <flux:error name="address.address1" />
                        </div>

                        <div class="sm:col-span-2">
                            <flux:input wire:model="address.address2" label="Address line 2" autocomplete="address-line2" />
                        </div>

                        <flux:input wire:model="address.city" label="City" autocomplete="address-level2" />
                        <flux:input wire:model="address.province_code" label="State / Province" autocomplete="address-level1" />
                        <flux:input wire:model="address.postal_code" label="Postal code" autocomplete="postal-code" />

                        <flux:select wire:model="address.country" label="Country" autocomplete="country">
                            <flux:select.option value="DE">Germany</flux:select.option>
                            <flux:select.option value="AT">Austria</flux:select.option>
                            <flux:select.option value="CH">Switzerland</flux:select.option>
                            <flux:select.option value="US">United States</flux:select.option>
                            <flux:select.option value="GB">United Kingdom</flux:select.option>
                        </flux:select>
                    </div>

                    <div class="space-y-1">
                        <flux:error name="address.first_name" />
                        <flux:error name="address.last_name" />
                        <flux:error name="address.city" />
                        <flux:error name="address.postal_code" />
                        <flux:error name="address.country" />
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <flux:button type="button" wire:click="cancelAddressForm" variant="filled">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </form>
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($addresses as $addressRecord)
                @php($addressData = $addressRecord->address_json ?? [])

                <article wire:key="customer-address-{{ $addressRecord->getKey() }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-zinc-950 dark:text-white">{{ $addressRecord->label ?: 'Address' }}</h3>
                                @if ($addressRecord->is_default)
                                    <flux:badge color="green">Default</flux:badge>
                                @endif
                            </div>
                            <div class="mt-3 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                <p>{{ trim(data_get($addressData, 'first_name').' '.data_get($addressData, 'last_name')) }}</p>
                                <p>{{ data_get($addressData, 'address1') }}</p>
                                @if (data_get($addressData, 'address2'))
                                    <p>{{ data_get($addressData, 'address2') }}</p>
                                @endif
                                <p>{{ trim(data_get($addressData, 'postal_code').' '.data_get($addressData, 'city')) }}</p>
                                <p>{{ data_get($addressData, 'country') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <flux:button wire:click="openAddressForm({{ $addressRecord->getKey() }})" size="sm" variant="filled">Edit</flux:button>
                        @unless ($addressRecord->is_default)
                            <flux:button wire:click="setDefaultAddress({{ $addressRecord->getKey() }})" size="sm" variant="ghost">Set default</flux:button>
                        @endunless
                        <flux:button wire:click="deleteAddress({{ $addressRecord->getKey() }})" wire:confirm="Delete this address?" size="sm" variant="danger">Delete</flux:button>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-zinc-200 px-4 py-10 text-center dark:border-zinc-800 md:col-span-2">
                    <flux:icon name="map-pin" class="mx-auto size-8 text-zinc-400" />
                    <p class="mt-3 font-medium text-zinc-950 dark:text-white">No addresses saved.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-storefront.account-shell>
