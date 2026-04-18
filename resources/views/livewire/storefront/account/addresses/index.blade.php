@php
    $formatAddress = function ($address): string {
        $json = $address->address_json ?? [];
        $lines = array_filter([
            trim(($json['first_name'] ?? '').' '.($json['last_name'] ?? '')),
            $json['company'] ?? null,
            $json['address1'] ?? null,
            $json['address2'] ?? null,
            trim(($json['city'] ?? '').' '.($json['postal_code'] ?? '')),
            $json['region'] ?? null,
            $json['country_code'] ?? null,
        ]);
        return implode("\n", $lines);
    };
@endphp
<div class="mx-auto max-w-5xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Account', 'url' => route('account.dashboard')],
        ['label' => 'Addresses'],
    ]" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">Your addresses</flux:heading>
        <flux:button variant="primary" wire:click="openCreate" data-testid="add-address">
            Add address
        </flux:button>
    </div>

    @if ($addresses->isEmpty())
        <flux:callout icon="map-pin">No addresses yet. Add one to speed up checkout.</flux:callout>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($addresses as $address)
                @php($isDefault = (bool) $address->is_default)
                <div wire:key="addr-{{ $address->id }}"
                     data-testid="address-card"
                     @class([
                        'rounded-lg border p-4 text-sm dark:bg-zinc-900',
                        'border-indigo-500 dark:border-indigo-400' => $isDefault,
                        'border-zinc-200 dark:border-zinc-800' => ! $isDefault,
                     ])>
                    @if ($isDefault)
                        <x-storefront.badge variant="accent" class="mb-2">Default</x-storefront.badge>
                    @endif
                    @if ($address->label)
                        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $address->label }}</div>
                    @endif
                    <pre class="mt-1 whitespace-pre-wrap font-sans text-zinc-700 dark:text-zinc-200">{{ $formatAddress($address) }}</pre>
                    <div class="mt-3 flex flex-wrap gap-3 text-sm">
                        <button type="button" wire:click="openEdit({{ $address->id }})" class="font-medium underline">Edit</button>
                        <button type="button" wire:click="delete({{ $address->id }})"
                                wire:confirm="Delete this address?"
                                class="font-medium text-rose-600 underline dark:text-rose-400">Delete</button>
                        @if (! $isDefault)
                            <button type="button" wire:click="setDefault({{ $address->id }})" class="font-medium underline" data-testid="set-default">Set as default</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($showForm)
        <div role="dialog" aria-modal="true" aria-label="Address form"
             class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/50 p-6">
            <div class="mt-12 w-full max-w-2xl rounded-lg border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
                    <flux:heading size="md">{{ $editingId ? 'Edit address' : 'Add address' }}</flux:heading>
                    <flux:button variant="ghost" icon="x-mark" aria-label="Close" wire:click="cancel" />
                </div>
                <form wire:submit="save" class="space-y-4 p-5">
                    <flux:field>
                        <flux:label>Label (optional)</flux:label>
                        <flux:input wire:model="label" placeholder="Home, Office..." />
                        <flux:error name="label" />
                    </flux:field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>First name</flux:label>
                            <flux:input wire:model="first_name" required />
                            <flux:error name="first_name" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Last name</flux:label>
                            <flux:input wire:model="last_name" required />
                            <flux:error name="last_name" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Company (optional)</flux:label>
                        <flux:input wire:model="company" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Address line 1</flux:label>
                        <flux:input wire:model="address1" required />
                        <flux:error name="address1" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Address line 2 (optional)</flux:label>
                        <flux:input wire:model="address2" />
                    </flux:field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>City</flux:label>
                            <flux:input wire:model="city" required />
                            <flux:error name="city" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Postal code</flux:label>
                            <flux:input wire:model="postal_code" required />
                            <flux:error name="postal_code" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Region / State</flux:label>
                            <flux:input wire:model="region" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Country (ISO 3166 alpha-2)</flux:label>
                            <flux:input wire:model="country_code" maxlength="2" placeholder="DE" required />
                            <flux:error name="country_code" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Phone (optional)</flux:label>
                        <flux:input wire:model="phone" type="tel" />
                    </flux:field>

                    <flux:checkbox wire:model="is_default" label="Set as default address" />

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <flux:button variant="ghost" wire:click="cancel" type="button">Cancel</flux:button>
                        <flux:button variant="primary" type="submit">
                            {{ $editingId ? 'Save changes' : 'Add address' }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
