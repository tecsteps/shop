<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.settings.index') }}" wire:navigate>Settings</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Shipping</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Shipping</flux:heading>
        <flux:button variant="primary" wire:click="openZoneModal()">Add zone</flux:button>
    </div>

    <div class="flex flex-col gap-4">
        @forelse ($zones as $zone)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900" wire:key="zone-{{ $zone->getKey() }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <flux:heading size="md">Zone: {{ $zone->name }}</flux:heading>
                    <div class="flex gap-2">
                        <flux:button variant="ghost" size="sm" wire:click="openZoneModal({{ $zone->getKey() }})">Edit</flux:button>
                        <flux:button variant="ghost" size="sm" wire:click="deleteZone({{ $zone->getKey() }})">Delete</flux:button>
                    </div>
                </div>
                <flux:text variant="subtle" class="mt-1 text-sm">Countries: {{ implode(', ', (array) ($zone->countries_json ?? [])) }}</flux:text>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                                <th class="pb-2 font-medium">Name</th>
                                <th class="pb-2 font-medium">Type</th>
                                <th class="pb-2 font-medium">Config</th>
                                <th class="pb-2 font-medium">Active</th>
                                <th class="pb-2 font-medium"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($zone->shippingRates as $rate)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="rate-{{ $rate->getKey() }}">
                                    <td class="py-2">{{ $rate->name }}</td>
                                    <td class="py-2"><flux:badge>{{ $rate->type->value }}</flux:badge></td>
                                    <td class="py-2">{{ $this->money((int) (is_array($rate->config_json) ? ($rate->config_json['amount'] ?? 0) : 0)) }}</td>
                                    <td class="py-2">
                                        <flux:switch :checked="(bool) $rate->is_active" wire:click="toggleRateActive({{ $rate->getKey() }})" aria-label="Toggle {{ $rate->name }}" />
                                    </td>
                                    <td class="py-2 text-right">
                                        <flux:button variant="ghost" size="sm" wire:click="openRateModal({{ $zone->getKey() }}, {{ $rate->getKey() }})">Edit</flux:button>
                                        <flux:button variant="ghost" size="sm" wire:click="deleteRate({{ $rate->getKey() }})">Delete</flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center"><flux:text variant="subtle">No rates configured.</flux:text></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <flux:button variant="ghost" size="sm" wire:click="openRateModal({{ $zone->getKey() }})" class="mt-3">Add rate</flux:button>
            </div>
        @empty
            <div class="rounded-lg border border-zinc-200 bg-white p-6 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text variant="subtle">No shipping zones configured yet.</flux:text>
            </div>
        @endforelse
    </div>

    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">Test shipping address</flux:heading>
        <flux:text variant="subtle" class="mt-1 text-sm">Enter an address to see which shipping zone and rates match.</flux:text>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <flux:select wire:model="testCountry" label="Country">
                @foreach ($countries as $code => $name)
                    <flux:select.option value="{{ $code }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="testState" label="State / Region" />
            <flux:input wire:model="testCity" label="City" />
            <flux:input wire:model="testZip" label="ZIP / Postal code" />
        </div>
        <flux:button variant="primary" wire:click="testShippingAddress" class="mt-4">Test</flux:button>
        @if ($this->testResult !== null)
            <div class="mt-4">
                @if ($this->testResult['matched'])
                    <p class="text-sm font-medium">Matched zone: {{ $this->testResult['zone_name'] }}</p>
                    <ul class="mt-2 text-sm">
                        @foreach ($this->testResult['rates'] as $rate)
                            <li>{{ $rate['name'] }} - {{ $rate['price'] }}</li>
                        @endforeach
                    </ul>
                @else
                    <flux:callout variant="warning" icon="exclamation-triangle" heading="No match.">
                        No shipping zone matches this address.
                    </flux:callout>
                @endif
            </div>
        @endif
    </div>

    <flux:modal wire:model="showZoneModal" class="max-w-md">
        <flux:heading size="lg">{{ $this->editingZoneId ? 'Edit shipping zone' : 'Add shipping zone' }}</flux:heading>
        <flux:input wire:model="zoneName" label="Zone name" placeholder="Domestic, Europe, International..." class="mt-4" />
        <p class="mt-4 text-sm font-medium">Countries</p>
        <div class="mt-2 grid max-h-56 grid-cols-2 gap-2 overflow-y-auto">
            @foreach ($countries as $code => $name)
                <label class="flex items-center gap-2 text-sm" wire:key="country-{{ $code }}">
                    <flux:checkbox wire:model="zoneCountries" value="{{ $code }}" />
                    <span>{{ $name }}</span>
                </label>
            @endforeach
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showZoneModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveZone">Save zone</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showRateModal" class="max-w-md">
        <flux:heading size="lg">{{ $this->editingRateId ? 'Edit shipping rate' : 'Add shipping rate' }}</flux:heading>
        <flux:input wire:model="rateName" label="Rate name" placeholder="Standard, Express..." class="mt-4" />
        <flux:select wire:model.live="rateType" label="Rate type" class="mt-3">
            <flux:select.option value="flat">Flat rate</flux:select.option>
            <flux:select.option value="weight">Weight-based</flux:select.option>
            <flux:select.option value="price">Price-based</flux:select.option>
            <flux:select.option value="carrier">Carrier-calculated</flux:select.option>
        </flux:select>
        @if ($this->rateType === 'flat')
            <flux:input wire:model="ratePrice" label="Price" type="number" step="0.01" min="0" class="mt-3" />
        @elseif ($this->rateType === 'weight')
            <div class="mt-3 grid grid-cols-3 gap-3">
                <flux:input wire:model="rateMinWeight" label="Min weight (g)" type="number" min="0" />
                <flux:input wire:model="rateMaxWeight" label="Max weight (g)" type="number" min="0" />
                <flux:input wire:model="ratePrice" label="Price" type="number" step="0.01" min="0" />
            </div>
        @elseif ($this->rateType === 'price')
            <div class="mt-3 grid grid-cols-3 gap-3">
                <flux:input wire:model="rateMinAmount" label="Min order" type="number" step="0.01" min="0" />
                <flux:input wire:model="rateMaxAmount" label="Max order" type="number" step="0.01" min="0" />
                <flux:input wire:model="ratePrice" label="Price" type="number" step="0.01" min="0" />
            </div>
        @else
            <flux:callout variant="info" icon="information-circle" heading="Carrier rates." class="mt-3">
                Carrier-calculated rates require a carrier integration to be configured.
            </flux:callout>
        @endif
        <div class="mt-3">
            <flux:switch wire:model.live="rateActive" label="Active" />
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showRateModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveRate">Save rate</flux:button>
        </div>
    </flux:modal>
</div>
