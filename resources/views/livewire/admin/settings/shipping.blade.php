<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Shipping</flux:heading>
        <flux:button variant="primary" wire:click="openZoneModal">
            <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
            Add zone
        </flux:button>
    </div>

    @foreach ($this->zones as $zone)
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900" wire:key="zone-{{ $zone->id }}">
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="md">{{ $zone->name }}</flux:heading>
                <div class="flex gap-2">
                    <flux:button variant="ghost" size="sm" wire:click="openZoneModal({{ $zone->id }})">Edit</flux:button>
                    <flux:button variant="ghost" size="sm" wire:click="deleteZone({{ $zone->id }})" wire:confirm="Delete this zone?">Delete</flux:button>
                </div>
            </div>
            <flux:text class="mb-3 text-sm text-gray-500">Countries: {{ implode(', ', $zone->countries ?? []) }}</flux:text>

            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-2 font-medium text-gray-500">Name</th>
                        <th class="pb-2 font-medium text-gray-500">Type</th>
                        <th class="pb-2 font-medium text-gray-500">Price</th>
                        <th class="pb-2 font-medium text-gray-500">Active</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($zone->rates as $rate)
                        <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="rate-{{ $rate->id }}">
                            <td class="py-2">{{ $rate->name }}</td>
                            <td class="py-2"><flux:badge size="sm">{{ $rate->type }}</flux:badge></td>
                            <td class="py-2">${{ number_format(($rate->config['price'] ?? 0) / 100, 2) }}</td>
                            <td class="py-2">
                                <flux:badge :color="$rate->is_active ? 'green' : 'zinc'" size="sm">
                                    {{ $rate->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </td>
                            <td class="py-2 text-right">
                                <flux:button variant="ghost" size="sm" wire:click="openRateModal({{ $zone->id }}, {{ $rate->id }})">Edit</flux:button>
                                <flux:button variant="ghost" size="sm" wire:click="deleteRate({{ $rate->id }})" wire:confirm="Delete this rate?">Delete</flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <flux:button variant="ghost" size="sm" wire:click="openRateModal({{ $zone->id }})" class="mt-3">
                <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
                Add rate
            </flux:button>
        </div>
    @endforeach

    {{-- Test Shipping Address --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <flux:heading size="md" class="mb-2">Test shipping address</flux:heading>
        <flux:text class="mb-4 text-sm text-gray-500">Enter an address to see which shipping zone and rates match.</flux:text>

        <div class="mb-4 grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Country</flux:label>
                <flux:input wire:model="testAddress.country" placeholder="US" />
            </flux:field>
            <flux:field>
                <flux:label>State/Region</flux:label>
                <flux:input wire:model="testAddress.state" />
            </flux:field>
            <flux:field>
                <flux:label>City</flux:label>
                <flux:input wire:model="testAddress.city" />
            </flux:field>
            <flux:field>
                <flux:label>ZIP/Postal code</flux:label>
                <flux:input wire:model="testAddress.zip" />
            </flux:field>
        </div>
        <flux:button wire:click="testShippingAddress">Test</flux:button>

        @if ($testResult)
            <div class="mt-4">
                @if ($testResult['zone'])
                    <flux:callout variant="info">
                        Matched zone: <strong>{{ $testResult['zone'] }}</strong>
                        @foreach ($testResult['rates'] as $rate)
                            <br>{{ $rate['name'] }} - ${{ number_format($rate['price'] / 100, 2) }}
                        @endforeach
                    </flux:callout>
                @else
                    <flux:callout variant="warning">No shipping zone matches this address.</flux:callout>
                @endif
            </div>
        @endif
    </div>

    {{-- Zone Modal --}}
    <flux:modal name="zone-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingZone ? 'Edit shipping zone' : 'Add shipping zone' }}</flux:heading>
            <flux:field>
                <flux:label>Zone name</flux:label>
                <flux:input wire:model="zoneName" placeholder="Domestic, Europe, International..." />
            </flux:field>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('zone-form').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveZone">Save zone</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Rate Modal --}}
    <flux:modal name="rate-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingRate ? 'Edit shipping rate' : 'Add shipping rate' }}</flux:heading>
            <flux:field>
                <flux:label>Rate name</flux:label>
                <flux:input wire:model="rateName" placeholder="Standard, Express..." />
            </flux:field>
            <flux:field>
                <flux:label>Rate type</flux:label>
                <flux:select wire:model.live="rateType">
                    <option value="flat">Flat rate</option>
                    <option value="weight">Weight-based</option>
                    <option value="price">Price-based</option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Price (cents)</flux:label>
                <flux:input wire:model="rateConfig.price" type="number" />
            </flux:field>
            <flux:switch wire:model="rateActive" label="Active" />
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('rate-form').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveRate">Save rate</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
