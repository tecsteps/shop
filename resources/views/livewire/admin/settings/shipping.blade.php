<div>
    <x-slot:title>Shipping Settings</x-slot:title>

    <flux:heading size="xl">Settings</flux:heading>

    <div class="mt-6 flex gap-4 border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('admin.settings.index') }}" wire:navigate class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">General</a>
        <a href="{{ route('admin.settings.shipping') }}" class="border-b-2 border-blue-600 px-4 py-2 text-sm font-medium text-blue-600">Shipping</a>
        <a href="{{ route('admin.settings.taxes') }}" wire:navigate class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Taxes</a>
    </div>

    <div class="mt-6">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Shipping Zones</flux:heading>
            @if (! $showZoneForm)
                <flux:button wire:click="openCreateForm" variant="primary" size="sm">Add Zone</flux:button>
            @endif
        </div>

        @if ($showZoneForm)
            <div class="mt-4 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">{{ $editingZoneId ? 'Edit Zone' : 'New Zone' }}</flux:heading>
                <form wire:submit="save" class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label>Zone Name</flux:label>
                        <flux:input wire:model="zoneName" />
                        <flux:error name="zoneName" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Countries (comma-separated codes)</flux:label>
                        <flux:input wire:model="countries" placeholder="DE, AT, CH" />
                        <flux:error name="countries" />
                    </flux:field>
                    <div class="flex gap-3">
                        <flux:button type="submit" variant="primary">Save</flux:button>
                        <flux:button wire:click="cancel" variant="ghost">Cancel</flux:button>
                    </div>
                </form>
            </div>
        @endif

        <div class="mt-4 space-y-4">
            @forelse ($zones as $zone)
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ $zone->name }}</p>
                            <p class="text-sm text-gray-500">{{ implode(', ', $zone->countries_json ?? []) }}</p>
                        </div>
                        <div class="flex gap-2">
                            <flux:button wire:click="openEditForm({{ $zone->id }})" size="sm" variant="ghost">Edit</flux:button>
                            <flux:button wire:click="deleteZone({{ $zone->id }})" wire:confirm="Are you sure?" size="sm" variant="ghost" class="text-red-600">Delete</flux:button>
                        </div>
                    </div>
                    @if ($zone->rates->isNotEmpty())
                        <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $zone->rates->count() }} rate(s) configured
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-lg border border-gray-200 p-8 text-center dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">No shipping zones configured yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
