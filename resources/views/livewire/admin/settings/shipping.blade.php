<div class="flex flex-col gap-6">
    <flux:heading size="xl">Shipping</flux:heading>

    <form wire:submit="addZone" class="flex items-end gap-2 rounded-xl bg-white p-4 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        <flux:input size="sm" label="Zone name" wire:model="zoneName" />
        <flux:input size="sm" label="Countries (CSV)" wire:model="zoneCountries" />
        <flux:button type="submit" size="sm" variant="primary">Add zone</flux:button>
    </form>

    @foreach ($zones as $zone)
        <div class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ $zone->name }}</flux:heading>
                <flux:subheading>{{ implode(', ', $zone->countries_json ?? []) }}</flux:subheading>
            </div>

            <div class="mt-4 space-y-2">
                @foreach ($zone->rates as $rate)
                    <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900">
                        <div>
                            <div class="font-medium">{{ $rate->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $rate->type->value }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span>{{ $currentStore->default_currency }} {{ number_format(($rate->config_json['amount'] ?? 0) / 100, 2) }}</span>
                            <flux:button size="xs" variant="ghost" wire:click="deleteRate({{ $rate->id }})">Remove</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>

            <form wire:submit="addRate({{ $zone->id }})" class="mt-3 flex items-end gap-2">
                <flux:input size="sm" label="Rate name" wire:model="rateName" />
                <flux:input size="sm" type="number" label="Amount (cents)" wire:model="rateAmount" />
                <flux:button type="submit" size="sm">Add rate</flux:button>
            </form>
        </div>
    @endforeach
</div>
