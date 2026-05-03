<div class="space-y-6">
    <div>
        <flux:heading size="xl">Apps</flux:heading>
        <flux:text>Installable integrations for store operations.</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($installedApps as $installation)
            <section wire:key="installed-app-{{ $installation->id }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <a href="{{ route('admin.apps.show', $installation->app->handle) }}" wire:navigate>
                        <flux:heading size="lg">{{ $installation->app->name }}</flux:heading>
                        <flux:text class="mt-2">Installed {{ $installation->installed_at?->diffForHumans() }}</flux:text>
                    </a>
                    <flux:badge color="green">{{ $installation->status }}</flux:badge>
                </div>
                <flux:button type="button" size="sm" class="mt-5" wire:click="uninstallApp({{ $installation->id }})">Uninstall</flux:button>
            </section>
        @endforeach

        @foreach ($availableApps as $app)
            <section wire:key="available-app-{{ $app->id }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">{{ $app->name }}</flux:heading>
                <flux:text class="mt-2">Available</flux:text>
                <flux:button type="button" size="sm" class="mt-5" wire:click="installApp({{ $app->id }})">Install</flux:button>
            </section>
        @endforeach
    </div>
</div>
