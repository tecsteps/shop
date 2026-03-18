<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Settings') }}</flux:heading>
    </div>

    <div class="flex gap-4 mb-6">
        <flux:button :href="route('admin.settings.index')" variant="ghost" wire:navigate>{{ __('General') }}</flux:button>
        <flux:button :href="route('admin.settings.shipping')" variant="primary" wire:navigate>{{ __('Shipping') }}</flux:button>
        <flux:button :href="route('admin.settings.taxes')" variant="ghost" wire:navigate>{{ __('Taxes') }}</flux:button>
    </div>

    <div class="space-y-6">
        {{-- Add Zone --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
            <flux:heading size="md" class="mb-4">{{ __('Add shipping zone') }}</flux:heading>
            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <flux:field>
                        <flux:label>{{ __('Zone name') }}</flux:label>
                        <flux:input wire:model="newZoneName" placeholder="{{ __('Domestic') }}" />
                        <flux:error name="newZoneName" />
                    </flux:field>
                </div>
                <div class="flex-1">
                    <flux:field>
                        <flux:label>{{ __('Countries (comma-separated)') }}</flux:label>
                        <flux:input wire:model="newZoneCountries" placeholder="{{ __('DE, AT, CH') }}" />
                    </flux:field>
                </div>
                <flux:button variant="primary" wire:click="createZone">{{ __('Add') }}</flux:button>
            </div>
        </div>

        {{-- Zones List --}}
        @foreach($this->zones as $zone)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:heading size="md">{{ $zone->name }}</flux:heading>
                        @if($zone->countries_json)
                            <flux:text class="text-sm text-zinc-500">{{ implode(', ', $zone->countries_json) }}</flux:text>
                        @endif
                    </div>
                    <flux:button size="sm" variant="ghost" wire:click="deleteZone({{ $zone->id }})" wire:confirm="{{ __('Delete this zone and all its rates?') }}" icon="trash" />
                </div>

                {{-- Rates --}}
                @if($zone->rates->count() > 0)
                    <table class="w-full text-sm mb-4">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="p-2 text-left font-medium text-zinc-500">{{ __('Rate name') }}</th>
                                <th class="p-2 text-left font-medium text-zinc-500">{{ __('Price') }}</th>
                                <th class="p-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($zone->rates as $rate)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="p-2">{{ $rate->name }}</td>
                                    <td class="p-2">${{ number_format(($rate->config_json['price'] ?? 0) / 100, 2) }}</td>
                                    <td class="p-2 text-right">
                                        <flux:button size="sm" variant="ghost" wire:click="deleteRate({{ $rate->id }})" icon="trash" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- Add Rate --}}
                <div class="flex gap-4 items-end">
                    <div class="flex-1">
                        <flux:input wire:model="newRateName" placeholder="{{ __('Standard Shipping') }}" size="sm" />
                    </div>
                    <div class="w-32">
                        <flux:input wire:model="newRatePrice" type="number" min="0" placeholder="{{ __('Price') }}" size="sm" />
                    </div>
                    <flux:button size="sm" wire:click="$set('selectedZoneId', {{ $zone->id }}); $call('addRate')">{{ __('Add rate') }}</flux:button>
                </div>
            </div>
        @endforeach
    </div>
</div>
