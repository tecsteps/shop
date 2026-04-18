<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Shipping</flux:heading>
        <flux:button variant="ghost" href="{{ route('admin.settings.index') }}" wire:navigate>Back</flux:button>
    </div>

    <form wire:submit="addZone" class="flex items-end gap-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:input wire:model="newZoneName" label="Zone name" />
        <flux:input wire:model="newZoneCountries" label="Countries (comma ISO-2)" />
        <flux:button type="submit" variant="primary" data-testid="add-zone">Add zone</flux:button>
    </form>

    @foreach ($zones as $zone)
        <div wire:key="zone-{{ $zone->id }}" class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ $zone->name }} <span class="text-xs text-zinc-500">{{ implode(', ', (array) $zone->countries_json) }}</span></flux:heading>
                <flux:button size="xs" variant="danger" wire:click="removeZone({{ $zone->id }})" wire:confirm="Delete zone?">Remove zone</flux:button>
            </div>

            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($zone->rates as $rate)
                    <li wire:key="rate-{{ $rate->id }}" class="flex items-center justify-between py-2 text-sm">
                        <span>{{ $rate->name }} ({{ $rate->type?->value }}) - {{ (int) ($rate->config_json['amount'] ?? 0) }} cents</span>
                        <flux:button size="xs" variant="danger" wire:click="removeRate({{ $rate->id }})">Remove</flux:button>
                    </li>
                @endforeach
            </ul>

            <div class="grid gap-2 sm:grid-cols-4">
                <flux:input wire:model="newRate.{{ $zone->id }}.name" placeholder="Rate name" />
                <flux:select wire:model="newRate.{{ $zone->id }}.type">
                    @foreach ($rateTypes as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->value }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="number" wire:model.number="newRate.{{ $zone->id }}.amount" placeholder="Amount (cents)" />
                <flux:button wire:click="addRate({{ $zone->id }})" data-testid="add-rate-{{ $zone->id }}">Add rate</flux:button>
            </div>
        </div>
    @endforeach
</div>
