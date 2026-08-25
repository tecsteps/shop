@php
    $countries = ['US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom', 'DE' => 'Germany', 'FR' => 'France', 'NL' => 'Netherlands', 'BE' => 'Belgium', 'AT' => 'Austria', 'CH' => 'Switzerland', 'ES' => 'Spain', 'IT' => 'Italy', 'PT' => 'Portugal', 'IE' => 'Ireland', 'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland', 'PL' => 'Poland', 'CZ' => 'Czech Republic', 'AU' => 'Australia', 'NZ' => 'New Zealand', 'JP' => 'Japan', 'SG' => 'Singapore'];
    $rateTypeLabels = ['flat' => 'Flat', 'weight' => 'Weight', 'price' => 'Price', 'carrier' => 'Carrier'];
@endphp

<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Shipping</flux:heading>

        <flux:button variant="primary" icon="plus" wire:click="openZoneModal">
            Add zone
        </flux:button>
    </div>

    {{-- Zones --}}
    <div class="mt-6 space-y-6">
        @forelse ($this->zones as $zone)
            <flux:card class="p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="md">{{ $zone->name }}</flux:heading>
                        <flux:text class="mt-1">Countries: {{ implode(', ', array_map(fn ($code) => $countries[$code] ?? $code, $zone->countries_json ?? [])) }}</flux:text>
                    </div>
                    <div class="flex gap-1">
                        <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="openZoneModal({{ $zone->id }})" aria-label="Edit zone" />
                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteZone({{ $zone->id }})" aria-label="Delete zone" />
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700">
                                <th class="py-2 pe-3 text-start font-medium">Name</th>
                                <th class="py-2 pe-3 text-start font-medium">Type</th>
                                <th class="py-2 pe-3 text-start font-medium">Config</th>
                                <th class="py-2 pe-3 text-start font-medium">Active</th>
                                <th class="py-2 text-end font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($zone->rates as $rate)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                    <td class="py-2.5 pe-3 font-medium text-zinc-800 dark:text-white">{{ $rate->name }}</td>
                                    <td class="py-2.5 pe-3">
                                        <flux:badge size="sm">{{ $rateTypeLabels[$rate->type] ?? $rate->type }}</flux:badge>
                                    </td>
                                    <td class="py-2.5 pe-3">{{ $rate->type === 'carrier' ? 'Carrier calculated' : ($this->rateSummary($rate)) }}</td>
                                    <td class="py-2.5 pe-3">
                                        <flux:switch
                                            :checked="(bool) ($rateActiveStates[$rate->id] ?? false)"
                                            wire:click="toggleRate({{ $rate->id }})"
                                        />
                                    </td>
                                    <td class="py-2.5 text-end">
                                        <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="openRateModal({{ $zone->id }}, {{ $rate->id }})" aria-label="Edit rate" />
                                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteRate({{ $rate->id }})" aria-label="Delete rate" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <flux:button variant="ghost" size="sm" icon="plus" wire:click="openRateModal({{ $zone->id }})" class="mt-3">
                    Add rate
                </flux:button>
            </flux:card>
        @empty
            <flux:card class="p-10 text-center">
                <flux:heading size="lg">No shipping zones</flux:heading>
                <flux:text class="mt-1">Create your first shipping zone to start offering rates.</flux:text>
                <flux:button variant="primary" icon="plus" wire:click="openZoneModal" class="mt-4">Add zone</flux:button>
            </flux:card>
        @endforelse
    </div>

    {{-- Test tool --}}
    <flux:card class="mt-6 p-6">
        <flux:heading size="md">Test shipping address</flux:heading>
        <flux:text class="mt-1">Enter an address to see which shipping zone and rates match.</flux:text>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <flux:field>
                <flux:label>Country</flux:label>
                <flux:select wire:model="testCountry">
                    @foreach ($countries as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>State / Region</flux:label>
                <flux:input wire:model="testState" />
            </flux:field>
            <flux:field>
                <flux:label>City</flux:label>
                <flux:input wire:model="testCity" />
            </flux:field>
            <flux:field>
                <flux:label>ZIP / Postal code</flux:label>
                <flux:input wire:model="testZip" />
            </flux:field>
        </div>

        <flux:button variant="primary" wire:click="testShippingAddress" class="mt-4">Test</flux:button>

        @if ($testNoMatch)
            <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                No shipping zone matches this address.
            </flux:callout>
        @elseif ($testResult)
            <div class="mt-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <p class="text-sm font-medium text-zinc-800 dark:text-white">Matched zone: {{ $testResult['zone'] }}</p>
                <ul class="mt-2 space-y-1 text-sm text-zinc-500 dark:text-zinc-300">
                    @foreach ($testResult['rates'] as $rate)
                        <li>{{ $rate['name'] }} — {{ $rate['summary'] }}</li>
                    @endforeach
                    @if ($testResult['rates'] === [])
                        <li>No active rates in this zone.</li>
                    @endif
                </ul>
            </div>
        @endif
    </flux:card>

    {{-- Zone modal --}}
    <flux:modal wire:model="showZoneModal" class="max-w-md">
        <flux:heading size="lg">{{ $editingZoneId ? 'Edit shipping zone' : 'Add shipping zone' }}</flux:heading>

        <div class="mt-4 space-y-4">
            <flux:field>
                <flux:label>Zone name</flux:label>
                <flux:input wire:model="zoneName" placeholder="Domestic, Europe, International..." />
                <flux:error name="zoneName" />
            </flux:field>

            <div>
                <flux:label>Countries</flux:label>
                <div class="mt-2 max-h-64 space-y-1 overflow-y-auto rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    @foreach ($countries as $code => $name)
                        <label class="flex items-center gap-2 text-sm">
                            <flux:checkbox wire:model="zoneCountries" value="{{ $code }}" />
                            {{ $name }}
                        </label>
                    @endforeach
                </div>
                <flux:error name="zoneCountries" />
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showZoneModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveZone">Save zone</flux:button>
        </div>
    </flux:modal>

    {{-- Rate modal --}}
    <flux:modal wire:model="showRateModal" class="max-w-md">
        <flux:heading size="lg">{{ $editingRateId ? 'Edit shipping rate' : 'Add shipping rate' }}</flux:heading>

        <div class="mt-4 space-y-4">
            <flux:field>
                <flux:label>Rate name</flux:label>
                <flux:input wire:model="rateName" placeholder="Standard, Express..." />
                <flux:error name="rateName" />
            </flux:field>

            <flux:field>
                <flux:label>Rate type</flux:label>
                <flux:select wire:model="rateType">
                    <option value="flat">Flat rate</option>
                    <option value="weight">Weight-based</option>
                    <option value="price">Price-based</option>
                    <option value="carrier">Carrier-calculated</option>
                </flux:select>
            </flux:field>

            @if ($rateType === 'flat')
                <flux:field>
                    <flux:label>Price</flux:label>
                    <flux:input wire:model="rateConfig.price" type="number" step="0.01" min="0" placeholder="5.00" />
                </flux:field>
            @elseif ($rateType === 'weight')
                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Min weight (g)</flux:label>
                        <flux:input wire:model="rateConfig.min_weight_g" type="number" min="0" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Max weight (g)</flux:label>
                        <flux:input wire:model="rateConfig.max_weight_g" type="number" min="0" />
                    </flux:field>
                </div>
                <flux:field>
                    <flux:label>Price</flux:label>
                    <flux:input wire:model="rateConfig.price" type="number" step="0.01" min="0" placeholder="5.00" />
                </flux:field>
            @elseif ($rateType === 'price')
                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Min order amount</flux:label>
                        <flux:input wire:model="rateConfig.min_amount" type="number" step="0.01" min="0" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Max order amount</flux:label>
                        <flux:input wire:model="rateConfig.max_amount" type="number" step="0.01" min="0" />
                    </flux:field>
                </div>
                <flux:field>
                    <flux:label>Price</flux:label>
                    <flux:input wire:model="rateConfig.price" type="number" step="0.01" min="0" placeholder="5.00" />
                </flux:field>
            @else
                <flux:callout variant="info" icon="information-circle">
                    Carrier-calculated rates require a carrier integration to be configured.
                </flux:callout>
            @endif

            <flux:switch wire:model="rateActive" label="Active" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showRateModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveRate">Save rate</flux:button>
        </div>
    </flux:modal>
</div>
