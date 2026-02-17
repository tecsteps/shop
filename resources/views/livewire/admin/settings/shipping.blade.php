<div>
    <div class="mb-6">
        <flux:heading size="xl">Settings</flux:heading>
    </div>

    <div class="mb-6 flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        <a href="{{ route('admin.settings.index') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">General</a>
        <a href="{{ route('admin.settings.shipping') }}" class="border-b-2 border-zinc-900 px-4 py-2 text-sm font-medium text-zinc-900 dark:border-white dark:text-white">Shipping</a>
        <a href="{{ route('admin.settings.taxes') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">Taxes</a>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <flux:heading size="lg">Shipping zones</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="openZoneModal()" size="sm">Add zone</flux:button>
    </div>

    @foreach ($zones as $zone)
        <div class="mb-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <flux:heading size="md">{{ $zone->name }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">{{ implode(', ', $zone->countries_json ?? []) }}</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="openZoneModal({{ $zone->id }})">Edit</flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="deleteZone({{ $zone->id }})" wire:confirm="Delete this zone?">Delete</flux:button>
                </div>
            </div>

            {{-- Rates --}}
            @if ($zone->rates->isNotEmpty())
                <table class="mb-3 w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Rate name</th>
                            <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Type</th>
                            <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Amount</th>
                            <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($zone->rates as $rate)
                            <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                <td class="py-2 text-zinc-900 dark:text-zinc-100">{{ $rate->name }}</td>
                                <td class="py-2"><flux:badge size="sm">{{ ucfirst($rate->type->value) }}</flux:badge></td>
                                <td class="py-2 text-zinc-600 dark:text-zinc-400">${{ number_format(($rate->config_json['amount'] ?? 0) / 100, 2) }}</td>
                                <td class="py-2 text-right">
                                    <flux:button size="sm" variant="ghost" wire:click="deleteRate({{ $rate->id }})" wire:confirm="Delete this rate?">Delete</flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <flux:button size="sm" variant="ghost" icon="plus" wire:click="openRateModal({{ $zone->id }})">Add rate</flux:button>
        </div>
    @endforeach

    @if ($zones->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-zinc-400">No shipping zones configured.</flux:text>
        </div>
    @endif

    {{-- Zone modal --}}
    <flux:modal name="zone-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingZoneId ? 'Edit zone' : 'Add zone' }}</flux:heading>
            <flux:input wire:model="zoneName" label="Zone name" placeholder="Domestic" />
            <flux:input wire:model="zoneCountries" label="Countries (comma-separated)" placeholder="US, CA" />
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('zone-form').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveZone">Save</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Rate modal --}}
    <flux:modal name="rate-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Add rate</flux:heading>
            <flux:input wire:model="rateName" label="Rate name" placeholder="Standard Shipping" />
            <flux:select wire:model="rateType" label="Type">
                <flux:select.option value="flat">Flat rate</flux:select.option>
                <flux:select.option value="weight">Weight-based</flux:select.option>
                <flux:select.option value="price">Price-based</flux:select.option>
            </flux:select>
            <flux:input wire:model="rateAmount" label="Amount" type="number" step="0.01" placeholder="5.99" />
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('rate-form').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveRate">Add rate</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
