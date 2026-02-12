<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Settings</flux:heading>
        <flux:button wire:click="openZoneModal" variant="primary" icon="plus">Add zone</flux:button>
    </div>

    {{-- Settings tabs --}}
    <div class="flex gap-4 border-b border-zinc-200 dark:border-zinc-700 mb-6">
        <a href="{{ route('admin.settings.index') }}" wire:navigate
            class="pb-2 text-sm font-medium border-b-2 {{ request()->routeIs('admin.settings.index') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
            General
        </a>
        <a href="{{ route('admin.settings.shipping') }}" wire:navigate
            class="pb-2 text-sm font-medium border-b-2 {{ request()->routeIs('admin.settings.shipping') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
            Shipping
        </a>
        <a href="{{ route('admin.settings.taxes') }}" wire:navigate
            class="pb-2 text-sm font-medium border-b-2 {{ request()->routeIs('admin.settings.taxes') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
            Taxes
        </a>
    </div>

    {{-- Shipping Zones --}}
    <div class="space-y-6">
        @forelse ($zones as $zone)
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <flux:heading size="md">{{ $zone->name }}</flux:heading>
                        @if ($zone->countries)
                            <flux:text class="text-zinc-500 mt-1">{{ implode(', ', $zone->countries) }}</flux:text>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <flux:button wire:click="openZoneModal({{ $zone->id }})" variant="ghost" size="sm">Edit</flux:button>
                        <flux:button wire:click="deleteZone({{ $zone->id }})" wire:confirm="Delete this shipping zone?" variant="ghost" size="sm" class="text-red-600">Delete</flux:button>
                    </div>
                </div>

                <div class="p-4">
                    @if ($zone->rates->count() > 0)
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-2 text-left font-medium text-zinc-500">Name</th>
                                    <th class="pb-2 text-left font-medium text-zinc-500">Type</th>
                                    <th class="pb-2 text-left font-medium text-zinc-500">Amount</th>
                                    <th class="pb-2 text-left font-medium text-zinc-500">Active</th>
                                    <th class="pb-2 text-right font-medium text-zinc-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($zone->rates as $rate)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                        <td class="py-2 text-zinc-900 dark:text-zinc-100">{{ $rate->name }}</td>
                                        <td class="py-2">
                                            <flux:badge size="sm">{{ ucfirst($rate->type->value) }}</flux:badge>
                                        </td>
                                        <td class="py-2 text-zinc-600 dark:text-zinc-400">
                                            @if (isset($rate->config_json['amount']))
                                                {{ \App\Support\MoneyFormatter::format($rate->config_json['amount']) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            <flux:badge size="sm" :color="$rate->is_active ? 'green' : 'zinc'">
                                                {{ $rate->is_active ? 'Active' : 'Inactive' }}
                                            </flux:badge>
                                        </td>
                                        <td class="py-2 text-right">
                                            <div class="flex justify-end gap-2">
                                                <flux:button wire:click="openRateModal({{ $zone->id }}, {{ $rate->id }})" variant="ghost" size="sm">Edit</flux:button>
                                                <flux:button wire:click="deleteRate({{ $rate->id }})" wire:confirm="Delete this rate?" variant="ghost" size="sm" class="text-red-600">Delete</flux:button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <flux:text class="text-zinc-500">No rates configured for this zone.</flux:text>
                    @endif

                    <div class="mt-4">
                        <flux:button wire:click="openRateModal({{ $zone->id }})" variant="ghost" size="sm" icon="plus">Add rate</flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <flux:icon name="truck" variant="outline" class="mx-auto size-12 text-zinc-400" />
                <flux:heading size="md" class="mt-4">No shipping zones</flux:heading>
                <flux:text class="mt-1 text-zinc-500">Add a shipping zone to start configuring shipping rates.</flux:text>
                <flux:button wire:click="openZoneModal" variant="primary" class="mt-4">Add zone</flux:button>
            </div>
        @endforelse
    </div>

    {{-- Zone Modal --}}
    @if ($showZoneModal)
        <flux:modal name="zone-form" :show="true" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $editingZoneId ? 'Edit shipping zone' : 'Add shipping zone' }}</flux:heading>

                <flux:field>
                    <flux:label>Zone name</flux:label>
                    <flux:input wire:model="zoneName" placeholder="Domestic, Europe, International..." />
                    <flux:error name="zoneName" />
                </flux:field>

                <flux:field>
                    <flux:label>Countries</flux:label>
                    <flux:textarea wire:model="zoneCountries" placeholder="Enter country codes separated by commas (US, CA, GB...)" rows="3" />
                    <flux:description>Enter ISO country codes separated by commas.</flux:description>
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="$set('showZoneModal', false)" variant="ghost">Cancel</flux:button>
                    <flux:button wire:click="saveZone" variant="primary">Save zone</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Rate Modal --}}
    @if ($showRateModal)
        <flux:modal name="rate-form" :show="true" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $editingRateId ? 'Edit shipping rate' : 'Add shipping rate' }}</flux:heading>

                <flux:field>
                    <flux:label>Rate name</flux:label>
                    <flux:input wire:model="rateName" placeholder="Standard, Express..." />
                    <flux:error name="rateName" />
                </flux:field>

                <flux:field>
                    <flux:label>Rate type</flux:label>
                    <flux:select wire:model.live="rateType">
                        <flux:select.option value="flat">Flat rate</flux:select.option>
                        <flux:select.option value="weight">Weight-based</flux:select.option>
                        <flux:select.option value="price">Price-based</flux:select.option>
                        <flux:select.option value="carrier">Carrier-calculated</flux:select.option>
                    </flux:select>
                </flux:field>

                @if ($rateType !== 'carrier')
                    <flux:field>
                        <flux:label>Amount</flux:label>
                        <flux:input wire:model="rateAmount" type="number" step="0.01" min="0" placeholder="0.00" />
                    </flux:field>
                @else
                    <flux:callout variant="info">
                        Carrier-calculated rates require a carrier integration to be configured.
                    </flux:callout>
                @endif

                <flux:field>
                    <flux:checkbox wire:model="rateActive" label="Active" />
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="$set('showRateModal', false)" variant="ghost">Cancel</flux:button>
                    <flux:button wire:click="saveRate" variant="primary">Save rate</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
