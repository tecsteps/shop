<div class="space-y-6">
    <flux:heading size="xl">Apps</flux:heading>

    {{-- Installed apps (spec 03 §15) --}}
    <div class="space-y-4">
        @if ($installedApps->isEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">No apps installed</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Installed apps will appear here. Install one from the catalog below to extend your store.</flux:text>
            </div>
        @else
            @foreach ($installedApps as $installation)
                <a href="{{ route('admin.apps.show', $installation) }}" wire:navigate
                   class="flex items-center gap-4 rounded-lg border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
                   wire:key="installation-{{ $installation->id }}">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="squares-2x2" class="size-6 text-zinc-500 dark:text-zinc-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <flux:heading size="md">{{ $installation->app?->name }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Installed {{ $installation->installed_at?->diffForHumans() ?? 'recently' }}</flux:text>
                    </div>
                    <flux:badge color="green">Active</flux:badge>
                </a>
            @endforeach
        @endif
    </div>

    <flux:separator />

    {{-- Available apps --}}
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Available apps</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Apps that can be installed on this store.</flux:text>
        </div>

        @if ($availableApps->isEmpty())
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">All catalog apps are installed.</flux:text>
        @else
            @foreach ($availableApps as $entry)
                <div class="flex items-center gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" wire:key="catalog-{{ $loop->index }}">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="squares-2x2" class="size-6 text-zinc-500 dark:text-zinc-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <flux:heading size="md">{{ $entry['name'] }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $entry['description'] }}</flux:text>
                    </div>
                    <flux:button variant="primary" wire:click="installApp({{ $loop->index }})">Install</flux:button>
                </div>
            @endforeach
        @endif
    </div>
</div>
