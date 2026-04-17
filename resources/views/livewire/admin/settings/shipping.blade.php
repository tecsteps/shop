<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Shipping</flux:heading>
        <div class="flex gap-2">
            <flux:button :href="route('admin.settings.index')" variant="ghost" wire:navigate>Back</flux:button>
            <flux:button variant="primary" icon="plus" wire:click="openZoneModal">New zone</flux:button>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($zones as $zone)
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">{{ $zone->name }}</flux:heading>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach (($zone->countries_json ?? []) as $country)
                                <flux:badge size="sm">{{ $country }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <flux:button size="sm" icon="plus" wire:click="openRateModal({{ $zone->id }})">Add rate</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="deleteZone({{ $zone->id }})" wire:confirm="Delete zone?">Delete</flux:button>
                    </div>
                </div>

                <div class="mt-4">
                    @if ($zone->rates->isEmpty())
                        <p class="text-sm text-zinc-500">No rates yet.</p>
                    @else
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Name</flux:table.column>
                                <flux:table.column>Type</flux:table.column>
                                <flux:table.column>Amount</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($zone->rates as $rate)
                                    <flux:table.row>
                                        <flux:table.cell>{{ $rate->name }}</flux:table.cell>
                                        <flux:table.cell>{{ $rate->type->value }}</flux:table.cell>
                                        <flux:table.cell>{{ number_format(($rate->config_json['amount'] ?? 0) / 100, 2) }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button size="xs" variant="danger" wire:click="deleteRate({{ $rate->id }})">Remove</flux:button>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
                No shipping zones configured.
            </div>
        @endforelse
    </div>

    <flux:modal wire:model.self="showZoneModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">New shipping zone</flux:heading>
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="zoneName" />
                <flux:error name="zoneName" />
            </flux:field>
            <flux:field>
                <flux:label>Countries (comma separated ISO codes)</flux:label>
                <flux:input wire:model="zoneCountries" placeholder="DE, AT, CH" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showZoneModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createZone">Create</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model.self="showRateModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">New shipping rate</flux:heading>
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="rateName" />
                <flux:error name="rateName" />
            </flux:field>
            <flux:field>
                <flux:label>Type</flux:label>
                <flux:select wire:model="rateType">
                    <flux:select.option value="flat">Flat</flux:select.option>
                    <flux:select.option value="weight">Weight</flux:select.option>
                    <flux:select.option value="price">Price</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Amount (in cents)</flux:label>
                <flux:input type="number" wire:model="rateAmount" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showRateModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createRate">Create</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
