<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Shipping Settings</flux:heading>
        <a href="{{ route('admin.settings.index') }}">
            <flux:button variant="ghost">Back to Settings</flux:button>
        </a>
    </div>

    <div class="flex items-center gap-3">
        <flux:button wire:click="openZoneForm" variant="primary">Add Shipping Zone</flux:button>
    </div>

    @if ($showZoneForm)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">{{ $editingZoneId ? 'Edit Zone' : 'New Shipping Zone' }}</flux:heading>
            <div class="space-y-4">
                <flux:input wire:model="zoneName" label="Zone Name" required />
                <flux:input wire:model="countries" label="Countries (comma separated)" placeholder="US, CA, GB" required />
                <div class="flex items-center gap-3">
                    <flux:button wire:click="saveZone" variant="primary">Save Zone</flux:button>
                    <flux:button wire:click="$set('showZoneForm', false)" variant="ghost">Cancel</flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($showRateForm)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Add Shipping Rate</flux:heading>
            <div class="space-y-4">
                <flux:input wire:model="rateName" label="Rate Name" required />
                <flux:input wire:model="rateAmount" label="Amount (cents)" type="number" required />
                <div class="flex items-center gap-3">
                    <flux:button wire:click="saveRate" variant="primary">Save Rate</flux:button>
                    <flux:button wire:click="$set('showRateForm', false)" variant="ghost">Cancel</flux:button>
                </div>
            </div>
        </div>
    @endif

    @foreach ($zones as $zone)
        <div wire:key="zone-{{ $zone->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="md">{{ $zone->name }}</flux:heading>
                    <flux:text class="text-sm">Countries: {{ is_array($zone->countries_json) ? implode(', ', $zone->countries_json) : '' }}</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:button wire:click="openRateForm({{ $zone->id }})" variant="ghost" size="sm">Add Rate</flux:button>
                    <flux:button wire:click="openZoneForm({{ $zone->id }})" variant="ghost" size="sm">Edit</flux:button>
                    <flux:button wire:click="deleteZone({{ $zone->id }})" variant="ghost" size="sm">Delete</flux:button>
                </div>
            </div>
            @if ($zone->rates->isNotEmpty())
                <div class="mt-3 space-y-1">
                    @foreach ($zone->rates as $rate)
                        <div wire:key="rate-{{ $rate->id }}" class="flex items-center justify-between rounded bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900">
                            <span>{{ $rate->name }} ({{ $rate->type->value }})</span>
                            <span>${{ number_format(($rate->config_json['amount'] ?? 0) / 100, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
