<div>
    <flux:heading size="xl">{{ __('Settings') }}</flux:heading>

    <div class="mt-6 flex gap-4 border-b border-zinc-200 dark:border-zinc-700">
        <button
            wire:click="$set('tab', 'general')"
            class="px-4 py-2 text-sm font-medium {{ $tab === 'general' ? 'border-b-2 border-zinc-800 dark:border-white' : 'text-zinc-500' }}"
        >{{ __('General') }}</button>
        <button
            wire:click="$set('tab', 'shipping')"
            class="px-4 py-2 text-sm font-medium {{ $tab === 'shipping' ? 'border-b-2 border-zinc-800 dark:border-white' : 'text-zinc-500' }}"
        >{{ __('Shipping') }}</button>
        <button
            wire:click="$set('tab', 'taxes')"
            class="px-4 py-2 text-sm font-medium {{ $tab === 'taxes' ? 'border-b-2 border-zinc-800 dark:border-white' : 'text-zinc-500' }}"
        >{{ __('Taxes') }}</button>
    </div>

    <div class="mt-6 max-w-2xl">
        @if($tab === 'general')
            <form wire:submit="saveGeneral" class="space-y-6">
                <flux:field>
                    <flux:input wire:model="storeName" :label="__('Store name')" required />
                </flux:field>
                <flux:field>
                    <flux:input wire:model="defaultCurrency" :label="__('Default currency')" required maxlength="3" />
                </flux:field>
                <flux:field>
                    <flux:input wire:model="timezone" :label="__('Timezone')" required />
                </flux:field>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </form>
        @elseif($tab === 'shipping')
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Shipping Zones') }}</flux:heading>
                @forelse($shippingZones as $zone)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:heading size="sm">{{ $zone->name }}</flux:heading>
                        <flux:text class="text-sm">
                            {{ __('Countries') }}: {{ implode(', ', $zone->countries_json ?? []) }}
                        </flux:text>
                        @if($zone->rates->isNotEmpty())
                            <div class="mt-2">
                                @foreach($zone->rates as $rate)
                                    <flux:text class="text-sm">{{ $rate->name }} ({{ ucfirst($rate->type) }})</flux:text>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <flux:text>{{ __('No shipping zones configured.') }}</flux:text>
                @endforelse
            </div>
        @elseif($tab === 'taxes')
            <form wire:submit="saveTax" class="space-y-6">
                <flux:field>
                    <flux:select wire:model="taxMode" :label="__('Tax mode')">
                        <flux:select.option value="manual">{{ __('Manual') }}</flux:select.option>
                        <flux:select.option value="provider">{{ __('Provider') }}</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:checkbox wire:model="pricesIncludeTax" :label="__('Prices include tax')" />
                </flux:field>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </form>
        @endif
    </div>
</div>
