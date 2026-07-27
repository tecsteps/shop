<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Your addresses</h1>
        <flux:button wire:click="create" variant="primary" icon="plus">Add new address</flux:button>
    </div>

    @if ($addresses->isEmpty())
        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">You have no saved addresses yet.</p>
    @else
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($addresses as $address)
                @php $data = $address->address_json ?? []; @endphp
                <div wire:key="address-{{ $address->id }}"
                     class="rounded-xl border p-5 {{ $address->is_default ? 'border-blue-500 dark:border-blue-500' : 'border-gray-200 dark:border-gray-800' }}">
                    <div class="flex items-center justify-between gap-2">
                        @if ($address->label)
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $address->label }}</p>
                        @else
                            <span></span>
                        @endif
                        @if ($address->is_default)
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-400">Default</span>
                        @endif
                    </div>

                    <address class="mt-2 text-sm not-italic text-gray-600 dark:text-gray-300">
                        {{ trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) }}<br>
                        @if (! empty($data['company'])){{ $data['company'] }}<br>@endif
                        {{ $data['address1'] ?? '' }}<br>
                        @if (! empty($data['address2'])){{ $data['address2'] }}<br>@endif
                        {{ $data['city'] ?? '' }}@if (! empty($data['province_code'] ?? $data['province'] ?? null)), {{ $data['province_code'] ?? $data['province'] }}@endif {{ $data['postal_code'] ?? '' }}<br>
                        {{ $data['country'] ?? $data['country_code'] ?? '' }}<br>
                        @if (! empty($data['phone'])){{ $data['phone'] }}<br>@endif
                    </address>

                    <div class="mt-4 flex items-center gap-4">
                        <button type="button" wire:click="edit({{ $address->id }})"
                                class="text-sm font-medium text-blue-600 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-blue-400">
                            Edit
                        </button>
                        <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="Delete this address?"
                                class="text-sm font-medium text-red-600 hover:underline focus:outline-hidden focus:ring-2 focus:ring-red-500 rounded dark:text-red-400">
                            Delete
                        </button>
                        @if (! $address->is_default)
                            <button type="button" wire:click="setDefault({{ $address->id }})"
                                    class="text-sm font-medium text-gray-600 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-300">
                                Set as default
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add/edit modal (spec 04 §10.6) --}}
    <flux:modal wire:model="showModal" name="address-form" class="max-h-[90vh] overflow-y-auto md:w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingId === null ? 'Add address' : 'Edit address' }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:field>
                    <flux:label for="address-label">Label (optional)</flux:label>
                    <flux:input id="address-label" type="text" wire:model="label" placeholder="Home, Work, ..." />
                    <flux:error name="label" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label for="address-first-name">First name</flux:label>
                        <flux:input id="address-first-name" type="text" wire:model="first_name" autocomplete="given-name" />
                        <flux:error name="first_name" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="address-last-name">Last name</flux:label>
                        <flux:input id="address-last-name" type="text" wire:model="last_name" autocomplete="family-name" />
                        <flux:error name="last_name" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label for="address-company">Company (optional)</flux:label>
                    <flux:input id="address-company" type="text" wire:model="company" autocomplete="organization" />
                    <flux:error name="company" />
                </flux:field>

                <flux:field>
                    <flux:label for="address-address1">Address</flux:label>
                    <flux:input id="address-address1" type="text" wire:model="address1" autocomplete="address-line1" />
                    <flux:error name="address1" />
                </flux:field>

                <flux:field>
                    <flux:label for="address-address2">Apartment, suite, etc. (optional)</flux:label>
                    <flux:input id="address-address2" type="text" wire:model="address2" autocomplete="address-line2" />
                    <flux:error name="address2" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label for="address-city">City</flux:label>
                        <flux:input id="address-city" type="text" wire:model="city" autocomplete="address-level2" />
                        <flux:error name="city" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="address-postal-code">Postal code</flux:label>
                        <flux:input id="address-postal-code" type="text" wire:model="postal_code" autocomplete="postal-code" />
                        <flux:error name="postal_code" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label for="address-province">Province / state (optional)</flux:label>
                        <flux:input id="address-province" type="text" wire:model="province" autocomplete="address-level1" />
                        <flux:error name="province" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="address-province-code">Province code (optional)</flux:label>
                        <flux:input id="address-province-code" type="text" wire:model="province_code" />
                        <flux:error name="province_code" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label for="address-country">Country</flux:label>
                        <flux:input id="address-country" type="text" wire:model="country" autocomplete="country-name" />
                        <flux:error name="country" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="address-country-code">Country code</flux:label>
                        <flux:input id="address-country-code" type="text" wire:model="country_code" autocomplete="country" placeholder="DE" />
                        <flux:error name="country_code" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label for="address-phone">Phone (optional)</flux:label>
                    <flux:input id="address-phone" type="text" wire:model="phone" autocomplete="tel" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:checkbox wire:model="is_default" label="Set as default address" />

                <div class="flex justify-end gap-3">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Save address</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
