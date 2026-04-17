<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Shipping</flux:heading>
    </div>

    <div class="flex flex-wrap gap-2 border-b border-neutral-200 pb-2 dark:border-neutral-800">
        <a href="{{ url('/admin/settings') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">General</a>
        <a href="{{ url('/admin/settings/shipping') }}" class="rounded-md px-3 py-1 text-sm font-medium bg-neutral-100 dark:bg-neutral-800">Shipping</a>
        <a href="{{ url('/admin/settings/taxes') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">Taxes</a>
        <a href="{{ url('/admin/settings/staff') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">Staff</a>
    </div>

    <form wire:submit="addZone" class="flex flex-wrap items-end gap-3 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <flux:field>
            <flux:label>Zone name</flux:label>
            <flux:input wire:model="newZoneName" placeholder="Domestic, Europe..." />
            <flux:error name="newZoneName" />
        </flux:field>
        <flux:button type="submit" variant="primary">Add zone</flux:button>
    </form>

    <div class="space-y-4">
        @forelse ($zones as $zone)
            <div wire:key="zone-{{ $zone->id }}" class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center justify-between">
                    <flux:heading size="md">{{ $zone->name }}</flux:heading>
                    <flux:button size="sm" variant="danger" wire:click="deleteZone({{ $zone->id }})" wire:confirm="Delete this zone?">Delete zone</flux:button>
                </div>
                <p class="mt-1 text-xs text-neutral-500">Countries: {{ implode(', ', $zone->countries_json ?? []) ?: 'None' }}</p>
                <table class="mt-3 w-full text-sm">
                    <thead class="text-left text-xs uppercase text-neutral-500">
                        <tr>
                            <th class="py-2">Rate</th>
                            <th class="py-2">Type</th>
                            <th class="py-2">Active</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($zone->rates as $rate)
                            <tr wire:key="rate-{{ $rate->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                                <td class="py-2">{{ $rate->name }}</td>
                                <td class="py-2">{{ $rate->type->value }}</td>
                                <td class="py-2">{{ $rate->is_active ? 'Yes' : 'No' }}</td>
                                <td class="py-2 text-right">
                                    <flux:button size="sm" variant="ghost" wire:click="deleteRate({{ $rate->id }})">Remove</flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="rounded border border-dashed border-neutral-200 p-6 text-center text-neutral-500 dark:border-neutral-800">No shipping zones yet.</div>
        @endforelse
    </div>
</div>
