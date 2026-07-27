<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Shipping</flux:heading>

        <flux:button variant="primary" icon="plus" wire:click="openZoneForm">Add zone</flux:button>
    </div>

    @if ($zones->isEmpty())
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="truck" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">Create your first shipping zone</flux:heading>
            <flux:text class="mt-1">Zones group countries and regions with their own shipping rates.</flux:text>
            <flux:button variant="primary" class="mt-6" wire:click="openZoneForm">Add zone</flux:button>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($zones as $zone)
                <div wire:key="zone-{{ $zone->id }}" class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <flux:heading size="md">{{ $zone->name }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                Countries: {{ implode(', ', $zone->countries_json ?? []) }}
                                @if (! empty($zone->regions_json))
                                    &middot; Regions: {{ implode(', ', $zone->regions_json) }}
                                @endif
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openZoneForm({{ $zone->id }})" aria-label="Edit {{ $zone->name }}" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteZone({{ $zone->id }})" wire:confirm="Delete this zone and its rates?" aria-label="Delete {{ $zone->name }}" />
                        </div>
                    </div>

                    @if ($zone->rates->isNotEmpty())
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[560px] text-left text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                                        <th class="py-2 pr-4 font-medium">Name</th>
                                        <th class="py-2 pr-4 font-medium">Type</th>
                                        <th class="py-2 pr-4 font-medium">Config</th>
                                        <th class="py-2 pr-4 font-medium">Active</th>
                                        <th class="py-2 font-medium"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($zone->rates as $rate)
                                        <tr wire:key="rate-{{ $rate->id }}">
                                            <td class="py-2 pr-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $rate->name }}</td>
                                            <td class="py-2 pr-4"><flux:badge size="sm">{{ $rate->type->value }}</flux:badge></td>
                                            <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-300">{{ $this->configSummary($rate->type, $rate->config_json) }}</td>
                                            <td class="py-2 pr-4">
                                                <flux:switch wire:click="toggleRate({{ $rate->id }})" :checked="$rate->is_active" aria-label="Toggle {{ $rate->name }}" />
                                            </td>
                                            <td class="py-2">
                                                <div class="flex items-center justify-end gap-1">
                                                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openRateForm({{ $zone->id }}, {{ $rate->id }})" aria-label="Edit {{ $rate->name }}" />
                                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteRate({{ $rate->id }})" aria-label="Delete {{ $rate->name }}" />
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-4">
                        <flux:button size="sm" variant="ghost" icon="plus" wire:click="openRateForm({{ $zone->id }})">Add rate</flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Zone modal (spec 03 §11.3) --}}
    <flux:modal wire:model="showZoneForm" name="zone-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingZoneId === null ? 'Add shipping zone' : 'Edit shipping zone' }}</flux:heading>

            <flux:field>
                <flux:label for="zoneName">Zone name</flux:label>
                <flux:input id="zoneName" wire:model.blur="zoneName" placeholder="Domestic, Europe, International..." />
                <flux:error name="zoneName" />
            </flux:field>

            <flux:field>
                <flux:label for="zoneCountries">Countries</flux:label>
                <flux:input id="zoneCountries" wire:model.blur="zoneCountries" placeholder="DE, FR, GB" />
                <flux:description>Comma-separated ISO country codes.</flux:description>
                <flux:error name="zoneCountries" />
            </flux:field>

            <flux:field>
                <flux:label for="zoneRegions">Regions</flux:label>
                <flux:input id="zoneRegions" wire:model.blur="zoneRegions" placeholder="CA, TX (optional)" />
                <flux:description>Optional comma-separated region/state codes.</flux:description>
                <flux:error name="zoneRegions" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showZoneForm', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveZone">Save zone</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Rate modal (spec 03 §11.3) --}}
    <flux:modal wire:model="showRateForm" name="rate-form" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingRateId === null ? 'Add shipping rate' : 'Edit shipping rate' }}</flux:heading>

            <flux:field>
                <flux:label for="rateName">Rate name</flux:label>
                <flux:input id="rateName" wire:model.blur="rateName" placeholder="Standard, Express..." />
                <flux:error name="rateName" />
            </flux:field>

            <flux:field>
                <flux:label for="rateType">Rate type</flux:label>
                <flux:select id="rateType" wire:model.live="rateType">
                    <flux:select.option value="flat">Flat rate</flux:select.option>
                    <flux:select.option value="weight">Weight-based</flux:select.option>
                    <flux:select.option value="price">Price-based</flux:select.option>
                    <flux:select.option value="carrier">Carrier-calculated</flux:select.option>
                </flux:select>
                <flux:error name="rateType" />
            </flux:field>

            @if ($rateType === 'flat')
                <flux:field>
                    <flux:label for="rateAmount">Amount</flux:label>
                    <flux:input id="rateAmount" type="number" min="0" wire:model.blur="rateAmount" placeholder="500" />
                    <flux:description>Amount in minor units (e.g. 500 = 5.00).</flux:description>
                    <flux:error name="rateAmount" />
                </flux:field>
            @elseif ($rateType === 'weight' || $rateType === 'price')
                <div class="space-y-2">
                    <flux:label>Ranges</flux:label>
                    <flux:error name="rateRanges" />
                    @foreach ($rateRanges as $index => $range)
                        <div wire:key="range-{{ $index }}" class="flex items-end gap-2">
                            @if ($rateType === 'weight')
                                <flux:field>
                                    <flux:label>Min (g)</flux:label>
                                    <flux:input type="number" min="0" wire:model.blur="rateRanges.{{ $index }}.min_g" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Max (g)</flux:label>
                                    <flux:input type="number" min="0" wire:model.blur="rateRanges.{{ $index }}.max_g" />
                                </flux:field>
                            @else
                                <flux:field>
                                    <flux:label>Min amount</flux:label>
                                    <flux:input type="number" min="0" wire:model.blur="rateRanges.{{ $index }}.min_amount" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Max amount</flux:label>
                                    <flux:input type="number" min="0" wire:model.blur="rateRanges.{{ $index }}.max_amount" placeholder="No max" />
                                </flux:field>
                            @endif
                            <flux:field>
                                <flux:label>Amount</flux:label>
                                <flux:input type="number" min="0" wire:model.blur="rateRanges.{{ $index }}.amount" />
                            </flux:field>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeRange({{ $index }})" aria-label="Remove range" />
                        </div>
                        <flux:error name="rateRanges.{{ $index }}.min_g" />
                        <flux:error name="rateRanges.{{ $index }}.max_g" />
                        <flux:error name="rateRanges.{{ $index }}.min_amount" />
                        <flux:error name="rateRanges.{{ $index }}.max_amount" />
                        <flux:error name="rateRanges.{{ $index }}.amount" />
                    @endforeach
                    <flux:button size="sm" variant="ghost" icon="plus" wire:click="addRange">Add range</flux:button>
                </div>
            @else
                <flux:callout icon="information-circle">
                    Carrier-calculated rates require a carrier integration to be configured.
                </flux:callout>
            @endif

            <div>
                <flux:switch wire:model="rateActive" :label="$rateActive ? 'Active' : 'Inactive'" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showRateForm', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveRate">Save rate</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
