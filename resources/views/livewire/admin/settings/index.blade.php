<div>
    <flux:heading size="xl">Settings</flux:heading>

    {{-- Tabs --}}
    <div class="mt-6 flex gap-6 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
        @foreach ([
            'general' => 'General',
            'domains' => 'Domains',
            'shipping' => 'Shipping',
            'taxes' => 'Taxes',
            'checkout' => 'Checkout',
            'notifications' => 'Notifications',
        ] as $value => $label)
            @if (in_array($value, ['shipping', 'taxes'], true))
                <a
                    href="{{ route($value === 'shipping' ? 'admin.settings.shipping' : 'admin.settings.taxes') }}"
                    wire:navigate
                    @class([
                        'shrink-0 border-b-2 pb-3 text-sm transition',
                        'border-zinc-900 font-semibold text-zinc-900 dark:border-white dark:text-white' => $tab === $value,
                        'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $tab !== $value,
                    ])
                >
                    {{ $label }}
                </a>
            @else
                <button
                    type="button"
                    wire:click="setTab('{{ $value }}')"
                    @class([
                        'shrink-0 border-b-2 pb-3 text-sm transition',
                        'border-zinc-900 font-semibold text-zinc-900 dark:border-white dark:text-white' => $tab === $value,
                        'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $tab !== $value,
                    ])
                >
                    {{ $label }}
                </button>
            @endif
        @endforeach
    </div>

    <div class="mt-8 max-w-3xl">
        @if ($tab === 'general')
            <flux:card class="p-6">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div>
                        <flux:heading size="md">Store details</flux:heading>
                        <flux:text class="mt-1">Basic information about your store.</flux:text>
                    </div>
                    <div class="space-y-4 lg:col-span-2">
                        <flux:field>
                            <flux:label>Store name</flux:label>
                            <flux:input wire:model="storeName" />
                            <flux:error name="storeName" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Store handle</flux:label>
                            <flux:input wire:model="storeHandle" disabled />
                            <flux:description>The store handle cannot be changed after creation.</flux:description>
                        </flux:field>
                    </div>
                </div>
            </flux:card>

            <flux:separator class="my-8" />

            <flux:card class="p-6">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div>
                        <flux:heading size="md">Defaults</flux:heading>
                        <flux:text class="mt-1">Currency, language, and timezone settings.</flux:text>
                    </div>
                    <div class="space-y-4 lg:col-span-2">
                        <flux:field>
                            <flux:label>Default currency</flux:label>
                            <flux:select wire:model="defaultCurrency">
                                @foreach (['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF', 'JPY'] as $currency)
                                    <option value="{{ $currency }}">{{ $currency }}</option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Default locale</flux:label>
                            <flux:select wire:model="defaultLocale">
                                <option value="en">English</option>
                                <option value="de">German</option>
                                <option value="fr">French</option>
                                <option value="es">Spanish</option>
                                <option value="it">Italian</option>
                                <option value="nl">Dutch</option>
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Timezone</flux:label>
                            <flux:select wire:model="timezone">
                                @foreach ($this->timezones as $zone)
                                    <option value="{{ $zone }}">{{ $zone }}</option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>
                </div>
            </flux:card>

            <div class="mt-6 flex justify-end">
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">Save</flux:button>
            </div>
        @elseif ($tab === 'domains')
            <flux:card class="overflow-hidden">
                <div class="flex items-center justify-between p-6">
                    <flux:heading size="md">Domains</flux:heading>
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="$set('showAddDomain', true)">
                        Add domain
                    </flux:button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-y border-zinc-200 bg-zinc-50 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800">
                                <th class="px-6 py-2.5 text-start font-medium">Hostname</th>
                                <th class="px-3 py-2.5 text-start font-medium">Type</th>
                                <th class="px-3 py-2.5 text-start font-medium">Primary</th>
                                <th class="px-3 py-2.5 text-start font-medium">TLS</th>
                                <th class="px-6 py-2.5 text-end font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->domains as $domain)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                    <td class="px-6 py-3 font-medium text-zinc-800 dark:text-white">{{ $domain->hostname }}</td>
                                    <td class="px-3 py-3">
                                        <flux:badge size="sm">{{ $domain->type }}</flux:badge>
                                    </td>
                                    <td class="px-3 py-3">
                                        @if ($domain->is_primary)
                                            <flux:badge color="green" size="sm">Primary</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <flux:badge size="sm">{{ $domain->tls_mode ?: 'none' }}</flux:badge>
                                    </td>
                                    <td class="px-6 py-3 text-end">
                                        @if (! $domain->is_primary)
                                            <flux:button variant="ghost" size="sm" wire:click="setPrimary({{ $domain->id }})">Set Primary</flux:button>
                                        @endif
                                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="removeDomain({{ $domain->id }})" aria-label="Delete domain" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-zinc-400">No domains configured.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        @else
            <flux:card class="p-10 text-center">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                    <flux:icon.cog-6-tooth class="size-6" />
                </div>
                <flux:heading size="lg" class="mt-4">{{ ucfirst($tab) }} settings</flux:heading>
                <flux:text class="mt-1">This section is coming soon.</flux:text>
            </flux:card>
        @endif
    </div>

    {{-- Add domain modal --}}
    <flux:modal wire:model="showAddDomain" class="max-w-md">
        <flux:heading size="lg">Add domain</flux:heading>

        <div class="mt-4 space-y-4">
            <flux:field>
                <flux:label>Hostname</flux:label>
                <flux:input wire:model="newHostname" placeholder="shop.example.com" />
                <flux:error name="newHostname" />
            </flux:field>
            <flux:field>
                <flux:label>Type</flux:label>
                <flux:select wire:model="newType">
                    <option value="storefront">Storefront</option>
                    <option value="admin">Admin</option>
                    <option value="api">API</option>
                </flux:select>
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showAddDomain', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="addDomain">Add domain</flux:button>
        </div>
    </flux:modal>
</div>
