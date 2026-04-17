<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Shipping</flux:heading>
        <flux:button variant="primary" wire:click="openZoneModal()">
            <flux:icon name="plus" class="size-4 mr-1" /> Add zone
        </flux:button>
    </div>

    {{-- Shipping zones --}}
    <div class="space-y-6">
        @forelse ($zones as $zone)
            <div wire:key="zone-{{ $zone->id }}" class="border border-zinc-200 dark:border-zinc-700 rounded-lg">
                <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <flux:heading size="lg">{{ $zone->name }}</flux:heading>
                        <flux:text class="mt-1">
                            Countries: {{ implode(', ', $zone->countries_json ?? []) ?: 'None' }}
                        </flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="openZoneModal({{ $zone->id }})">Edit</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="deleteZone({{ $zone->id }})" wire:confirm="Delete this shipping zone and all its rates?">
                            <flux:icon name="trash" class="size-4 text-red-500" />
                        </flux:button>
                    </div>
                </div>

                <div class="p-4">
                    @if ($zone->rates->isNotEmpty())
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Name</th>
                                    <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Type</th>
                                    <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Config</th>
                                    <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Active</th>
                                    <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($zone->rates as $rate)
                                    <tr wire:key="rate-{{ $rate->id }}">
                                        <td class="py-2 text-zinc-900 dark:text-zinc-100">{{ $rate->name }}</td>
                                        <td class="py-2">
                                            <flux:badge size="sm">{{ ucfirst($rate->type->value) }}</flux:badge>
                                        </td>
                                        <td class="py-2 text-zinc-600 dark:text-zinc-400">
                                            @if (isset($rate->config_json['price']))
                                                ${{ number_format($rate->config_json['price'] / 100, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            @if ($rate->is_active)
                                                <flux:badge size="sm" color="green">Active</flux:badge>
                                            @else
                                                <flux:badge size="sm">Inactive</flux:badge>
                                            @endif
                                        </td>
                                        <td class="py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button size="sm" variant="ghost" wire:click="openRateModal({{ $zone->id }}, {{ $rate->id }})">Edit</flux:button>
                                                <flux:button size="sm" variant="ghost" wire:click="deleteRate({{ $rate->id }})" wire:confirm="Delete this rate?">
                                                    <flux:icon name="trash" class="size-4 text-red-500" />
                                                </flux:button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <flux:text class="text-center py-4">No rates configured for this zone.</flux:text>
                    @endif

                    <div class="mt-3">
                        <flux:button size="sm" variant="ghost" wire:click="openRateModal({{ $zone->id }})">
                            <flux:icon name="plus" class="size-4 mr-1" /> Add rate
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <flux:icon name="truck" class="size-12 mx-auto text-zinc-400 dark:text-zinc-500 mb-4" />
                <flux:heading size="lg">No shipping zones</flux:heading>
                <flux:text class="mt-1">Create your first shipping zone to configure delivery options.</flux:text>
                <div class="mt-4">
                    <flux:button variant="primary" wire:click="openZoneModal()">Add zone</flux:button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Test shipping address --}}
    <div class="mt-8 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
        <flux:heading size="lg">Test shipping address</flux:heading>
        <flux:text class="mt-1">Enter an address to see which shipping zone and rates match.</flux:text>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            <flux:input wire:model="testCountry" label="Country code" placeholder="US" />
            <flux:input wire:model="testState" label="State/Region" placeholder="CA" />
            <flux:input wire:model="testCity" label="City" placeholder="San Francisco" />
            <flux:input wire:model="testZip" label="ZIP/Postal code" placeholder="94102" />
        </div>

        <div class="mt-4">
            <flux:button wire:click="testShippingAddress">Test</flux:button>
        </div>

        @if ($testResult)
            <div class="mt-4">
                @if ($testResult['matched'])
                    <flux:callout>
                        <strong>Matched zone: {{ $testResult['zone_name'] }}</strong>
                        @if (!empty($testResult['rates']))
                            <ul class="mt-2 space-y-1">
                                @foreach ($testResult['rates'] as $rate)
                                    <li>{{ $rate['name'] }} - ${{ number_format($rate['price'] / 100, 2) }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </flux:callout>
                @else
                    <flux:callout variant="warning">No shipping zone matches this address.</flux:callout>
                @endif
            </div>
        @endif
    </div>

    {{-- Zone modal --}}
    <flux:modal wire:model="showZoneModal" name="zone-form" class="max-w-md">
        <form wire:submit="saveZone" class="space-y-4">
            <flux:heading size="lg">{{ $editingZoneId ? 'Edit shipping zone' : 'Add shipping zone' }}</flux:heading>

            <flux:input
                wire:model="zoneName"
                label="Zone name"
                placeholder="Domestic, Europe, International..."
                required
            />
            @error('zoneName')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Countries</label>
                <div class="max-h-48 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg p-3 space-y-1">
                    @foreach (['US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom', 'DE' => 'Germany', 'FR' => 'France', 'NL' => 'Netherlands', 'BE' => 'Belgium', 'AT' => 'Austria', 'CH' => 'Switzerland', 'ES' => 'Spain', 'IT' => 'Italy', 'PT' => 'Portugal', 'AU' => 'Australia', 'JP' => 'Japan', 'CN' => 'China', 'KR' => 'South Korea', 'BR' => 'Brazil', 'MX' => 'Mexico', 'IN' => 'India', 'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland', 'PL' => 'Poland', 'IE' => 'Ireland'] as $code => $name)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="zoneCountries" value="{{ $code }}" class="rounded border-zinc-300 dark:border-zinc-600" />
                            <span class="text-zinc-700 dark:text-zinc-300">{{ $name }} ({{ $code }})</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button variant="ghost" wire:click="$set('showZoneModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save zone</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Rate modal --}}
    <flux:modal wire:model="showRateModal" name="rate-form" class="max-w-md">
        <form wire:submit="saveRate" class="space-y-4">
            <flux:heading size="lg">{{ $editingRateId ? 'Edit shipping rate' : 'Add shipping rate' }}</flux:heading>

            <flux:input
                wire:model="rateName"
                label="Rate name"
                placeholder="Standard, Express..."
                required
            />

            <flux:select wire:model.live="rateType" label="Rate type">
                <option value="flat">Flat rate</option>
                <option value="weight">Weight-based</option>
                <option value="price">Price-based</option>
                <option value="carrier">Carrier-calculated</option>
            </flux:select>

            @if ($rateType === 'flat')
                <flux:input
                    wire:model="rateConfig.price"
                    label="Price (in cents)"
                    type="number"
                    placeholder="500"
                />
            @elseif ($rateType === 'weight')
                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="rateConfig.min_weight" label="Min weight (g)" type="number" />
                    <flux:input wire:model="rateConfig.max_weight" label="Max weight (g)" type="number" />
                </div>
                <flux:input wire:model="rateConfig.price" label="Price (in cents)" type="number" />
            @elseif ($rateType === 'price')
                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="rateConfig.min_amount" label="Min order amount (cents)" type="number" />
                    <flux:input wire:model="rateConfig.max_amount" label="Max order amount (cents)" type="number" />
                </div>
                <flux:input wire:model="rateConfig.price" label="Price (in cents)" type="number" />
            @elseif ($rateType === 'carrier')
                <flux:callout>Carrier-calculated rates require a carrier integration to be configured.</flux:callout>
            @endif

            <div class="flex items-center gap-2">
                <flux:switch wire:model="rateActive" />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button variant="ghost" wire:click="$set('showRateModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save rate</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
