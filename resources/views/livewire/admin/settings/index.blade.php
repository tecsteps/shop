<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">Settings</flux:heading>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        @foreach(['general' => 'General', 'domains' => 'Domains', 'shipping' => 'Shipping', 'taxes' => 'Taxes'] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                @class([
                    'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition',
                    'border-blue-500 text-blue-600 dark:text-blue-400' => $tab === $key,
                    'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' => $tab !== $key,
                ])
            >{{ $label }}</button>
        @endforeach
    </div>

    {{-- General --}}
    @if($tab === 'general')
        <div class="max-w-2xl space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <flux:heading size="md">Store details</flux:heading>
                <flux:input wire:model="storeName" label="Store name" />
                <div>
                    <flux:input wire:model="storeHandle" label="Handle" disabled />
                    <flux:text class="mt-1 text-xs text-zinc-500">The store handle cannot be changed after creation.</flux:text>
                </div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <flux:heading size="md">Defaults</flux:heading>
                <flux:select wire:model="defaultCurrency" label="Default currency">
                    <flux:select.option value="USD">USD</flux:select.option>
                    <flux:select.option value="EUR">EUR</flux:select.option>
                    <flux:select.option value="GBP">GBP</flux:select.option>
                </flux:select>
                <flux:select wire:model="defaultLocale" label="Default locale">
                    <flux:select.option value="en">English</flux:select.option>
                    <flux:select.option value="de">German</flux:select.option>
                    <flux:select.option value="fr">French</flux:select.option>
                </flux:select>
                <flux:select wire:model="timezone" label="Timezone">
                    <flux:select.option value="UTC">UTC</flux:select.option>
                    <flux:select.option value="America/New_York">America/New_York</flux:select.option>
                    <flux:select.option value="Europe/London">Europe/London</flux:select.option>
                    <flux:select.option value="Europe/Berlin">Europe/Berlin</flux:select.option>
                    <flux:select.option value="Asia/Tokyo">Asia/Tokyo</flux:select.option>
                </flux:select>
            </div>
            <flux:button variant="primary" wire:click="saveGeneral">Save</flux:button>
        </div>
    @endif

    {{-- Domains --}}
    @if($tab === 'domains')
        <div class="max-w-2xl space-y-4">
            <div class="flex justify-end">
                <flux:modal.trigger name="add-domain">
                    <flux:button variant="primary" icon="plus">Add domain</flux:button>
                </flux:modal.trigger>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Hostname</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Primary</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->domains as $domain)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $domain->hostname }}</flux:table.cell>
                            <flux:table.cell>{{ ucfirst($domain->type instanceof \App\Enums\StoreDomainType ? $domain->type->value : $domain->type) }}</flux:table.cell>
                            <flux:table.cell>
                                @if($domain->is_primary)
                                    <flux:badge size="sm" color="green">Primary</flux:badge>
                                @else
                                    <flux:button size="sm" variant="ghost" wire:click="setPrimary({{ $domain->id }})">Set Primary</flux:button>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if(!$domain->is_primary)
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteDomain({{ $domain->id }})" />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        <flux:modal name="add-domain" class="md:w-96">
            <div class="space-y-4">
                <flux:heading size="lg">Add domain</flux:heading>
                <flux:input wire:model="newHostname" label="Hostname" placeholder="shop.example.com" />
                <flux:select wire:model="newDomainType" label="Type">
                    <flux:select.option value="storefront">Storefront</flux:select.option>
                    <flux:select.option value="admin">Admin</flux:select.option>
                </flux:select>
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                    <flux:button variant="primary" wire:click="addDomain">Add domain</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Shipping --}}
    @if($tab === 'shipping')
        <div class="max-w-2xl space-y-4">
            <div class="flex justify-end">
                <flux:button variant="primary" icon="plus" wire:click="$set('editZoneId', null); $set('zoneName', ''); $set('zoneCountries', ''); $dispatch('flux-modal-show', { name: 'zone-form' })">Add zone</flux:button>
            </div>
            @foreach($this->shippingZones as $zone)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <flux:heading size="md">{{ $zone->name }}</flux:heading>
                            <flux:text class="text-xs text-zinc-500">{{ implode(', ', $zone->countries_json ?? []) }}</flux:text>
                        </div>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="editZone({{ $zone->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteZone({{ $zone->id }})" />
                        </div>
                    </div>
                    @foreach($zone->rates as $rate)
                        <div class="flex items-center justify-between border-t border-zinc-100 py-2 text-sm dark:border-zinc-800">
                            <span>{{ $rate->name }} - ${{ number_format((data_get($rate->config_json, 'price', 0)) / 100, 2) }}</span>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteRate({{ $rate->id }})" />
                        </div>
                    @endforeach
                    <flux:button size="sm" variant="ghost" class="mt-2" wire:click="openAddRate({{ $zone->id }})">Add rate</flux:button>
                </div>
            @endforeach
        </div>

        <flux:modal name="zone-form" class="md:w-96">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $editZoneId ? 'Edit' : 'Add' }} shipping zone</flux:heading>
                <flux:input wire:model="zoneName" label="Zone name" placeholder="Domestic" />
                <flux:input wire:model="zoneCountries" label="Countries (comma-separated)" placeholder="US, CA" />
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                    <flux:button variant="primary" wire:click="saveZone">Save zone</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="rate-form" class="md:w-96">
            <div class="space-y-4">
                <flux:heading size="lg">Add shipping rate</flux:heading>
                <flux:input wire:model="rateName" label="Rate name" placeholder="Standard Shipping" />
                <flux:select wire:model="rateType" label="Type">
                    <flux:select.option value="flat_rate">Flat rate</flux:select.option>
                    <flux:select.option value="weight_based">Weight-based</flux:select.option>
                    <flux:select.option value="price_based">Price-based</flux:select.option>
                </flux:select>
                <flux:input type="number" wire:model="ratePrice" label="Price (cents)" min="0" />
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                    <flux:button variant="primary" wire:click="saveRate">Save rate</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Taxes --}}
    @if($tab === 'taxes')
        <div class="max-w-2xl space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <flux:heading size="md">Tax configuration</flux:heading>
                <flux:select wire:model="taxMode" label="Mode">
                    <flux:select.option value="manual">Manual tax rates</flux:select.option>
                    <flux:select.option value="provider">Tax provider</flux:select.option>
                </flux:select>
                <flux:input wire:model="taxName" label="Tax name" placeholder="Tax" />
                <flux:input type="number" wire:model="taxRate" label="Tax rate (basis points, e.g. 1000 = 10%)" min="0" />
                <flux:checkbox wire:model="pricesIncludeTax" label="Prices include tax" />
            </div>
            <flux:button variant="primary" wire:click="saveTax">Save</flux:button>
        </div>
    @endif
</div>
