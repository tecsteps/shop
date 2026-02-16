<div>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Shipping Zones</h2>
        <flux:button wire:click="openZoneModal" variant="primary">Add zone</flux:button>
    </div>

    <div class="space-y-4">
        @foreach($zones as $zone)
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $zone->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ is_array($zone->countries_json) ? implode(', ', $zone->countries_json) : '' }}</p>
                    </div>
                    <div class="flex gap-2">
                        <flux:button wire:click="openZoneModal({{ $zone->id }})" variant="ghost" size="sm">Edit</flux:button>
                        <flux:button wire:click="deleteZone({{ $zone->id }})" variant="danger" size="sm">Delete</flux:button>
                    </div>
                </div>
                {{-- Rates --}}
                <table class="w-full text-sm">
                    <thead><tr class="border-b dark:border-zinc-700"><th class="py-2 text-left text-gray-500 dark:text-gray-400">Rate</th><th class="py-2 text-left text-gray-500 dark:text-gray-400">Type</th><th class="py-2 text-right text-gray-500 dark:text-gray-400">Amount</th><th class="py-2"></th></tr></thead>
                    <tbody>
                        @foreach($zone->rates as $rate)
                            <tr class="border-b dark:border-zinc-700">
                                <td class="py-2 text-gray-900 dark:text-white">{{ $rate->name }}</td>
                                <td class="py-2 text-gray-500 dark:text-gray-400">{{ ucfirst($rate->type->value ?? $rate->type) }}</td>
                                <td class="py-2 text-right text-gray-900 dark:text-white">${{ number_format($rate->amount / 100, 2) }}</td>
                                <td class="py-2 text-right"><flux:button wire:click="deleteRate({{ $rate->id }})" variant="ghost" size="sm" icon="x-mark" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <flux:button wire:click="openRateModal({{ $zone->id }})" variant="ghost" size="sm" class="mt-2">Add rate</flux:button>
            </div>
        @endforeach
    </div>

    {{-- Zone Modal --}}
    <flux:modal wire:model="showZoneModal">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold">{{ $editingZoneId ? 'Edit' : 'Add' }} Shipping Zone</h3>
            <flux:input wire:model="zoneName" label="Zone name" />
            <flux:input wire:model="zoneCountries" label="Countries (comma separated)" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showZoneModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveZone" variant="primary">Save</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Rate Modal --}}
    <flux:modal wire:model="showRateModal">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold">Add Rate</h3>
            <flux:input wire:model="rateName" label="Rate name" />
            <flux:input wire:model="rateAmount" label="Amount ($)" type="number" step="0.01" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showRateModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveRate" variant="primary">Add rate</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
