<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">Shipping</flux:heading>
            <flux:text>Zones and flat rates used during checkout.</flux:text>
        </div>

        <flux:button :href="route('admin.settings.index')" wire:navigate>Back to settings</flux:button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
                <flux:heading size="lg">Zones</flux:heading>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($zones as $zone)
                    <div wire:key="shipping-zone-{{ $zone->id }}" class="p-5">
                        <div class="font-medium">{{ $zone->name }}</div>
                        <div class="text-sm text-zinc-500">{{ implode(', ', $zone->countries_json ?? []) }}</div>
                        <div class="mt-4 space-y-2">
                            @foreach ($zone->rates as $rate)
                                <div wire:key="shipping-rate-{{ $rate->id }}" class="flex items-center justify-between gap-4 rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                                    <span>{{ $rate->name }} · {{ \Illuminate\Support\Number::currency(((int) data_get($rate->config_json, 'amount', 0)) / 100, app('current_store')->default_currency) }}</span>
                                    <flux:button size="sm" wire:click="deleteRate({{ $rate->id }})">Delete</flux:button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="space-y-6">
            <form wire:submit="createZone" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">New zone</flux:heading>
                <div class="mt-4 grid gap-4">
                    <flux:input wire:model="zoneName" label="Name" />
                    <flux:input wire:model="countries" label="Countries" placeholder="DE, AT, CH" />
                    <flux:button type="submit">Create zone</flux:button>
                </div>
            </form>

            <form wire:submit="createRate" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">New flat rate</flux:heading>
                <div class="mt-4 grid gap-4">
                    <flux:select wire:model="rateZoneId" label="Zone">
                        <option value="">Choose zone</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="rateName" label="Name" />
                    <flux:input wire:model="rateAmount" type="number" min="0" label="Amount cents" />
                    <flux:button type="submit">Create rate</flux:button>
                </div>
            </form>
        </aside>
    </div>
</div>
